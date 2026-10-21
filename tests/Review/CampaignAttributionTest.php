<?php

namespace Tests\Review;

use App\{Piece, Tag, User};
use App\Billing\{Payment, Plan};
use App\Billing\Factories\StripeFactory;
use App\Billing\Webhooks\StripeWebhooks;
use App\Services\{CampaignAttribution, CampaignAttributionReport, CampaignParameters, StripePaymentReceipts};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Cache, Cookie, DB, Event, Notification, Redis};

class CampaignAttributionTest extends ReviewTestCase
{
    private $piece, $user, $attribution;

    public function setUp(): void
    {
        parent::setUp();
        config(['attribution.enabled' => true]);
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class, \App\Http\Middleware\ValidateRegistrationForm::class]);
        Event::fake();
        Notification::fake();
        Redis::shouldReceive('get')->andReturn(null);
        Model::withoutEvents(function () {
            $this->user = create(User::class, ['password' => bcrypt('review-password')])->setAppends(['full_name']);
            $this->piece = create(Piece::class, ['id' => 174, 'name' => 'Notturno attribution fixture']);
            foreach (['level' => 'elementary', 'period' => 'romantic', 'length' => 'short'] as $type => $name) {
                $this->piece->tags()->attach(create(Tag::class, compact('type', 'name')));
            }
        });
        $this->attribution = new CampaignAttribution;
    }

    private function landing(array $overrides = [])
    {
        return $this->get(route('webapp.pieces.show', array_merge([
            'piece' => $this->piece->id, 'utm_source' => 'youtube', 'utm_medium' => 'organic_video',
            'utm_campaign' => 'respighi_notturno', 'utm_content' => 'description',
        ], $overrides)))->assertOk();
    }

    private function token($response)
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        return optional((new \DOMXPath($dom))->query('//*[@data-campaign-measurement]')->item(0))->getAttribute('data-token') ?? '';
    }

    private function grant()
    {
        $token = $this->token($this->landing());
        $this->withCookie(config('session.cookie'), session()->getId());
        $response = $this->post(route('webapp.attribution.consent'), ['choice' => 'granted', 'token' => $token])->assertOk();
        $visit = DB::table('campaign_visits')->first();
        $this->withCookies([CampaignAttribution::CONSENT_COOKIE => 'v1:granted', CampaignAttribution::VISIT_COOKIE => $visit->id]);
        return [$visit, $token, $response];
    }

    private function subscription($id = 'sub_first', $status = 'trialing')
    {
        return (object) ['id' => $id, 'customer' => 'cus_first', 'status' => $status,
            'created' => now()->timestamp, 'trial_start' => now()->timestamp, 'trial_end' => now()->addWeek()->timestamp,
            'plan' => (object) ['id' => 'monthly']];
    }

    private function customer($subscription)
    {
        return (object) ['id' => 'cus_first', 'subscriptions' => (object) ['data' => [$subscription]],
            'sources' => (object) ['data' => [(object) ['brand' => 'visa', 'last4' => '4242']]]];
    }

    private function invoice($id = 'in_first', $subscription = 'sub_first', $amount = 999, $paidAt = null, $customer = 'cus_first')
    {
        return ['id' => 'evt_review', 'type' => 'invoice.payment_succeeded', 'created' => now()->timestamp,
            'data' => ['object' => ['id' => $id, 'subscription' => $subscription, 'customer' => $customer,
                'paid' => true, 'amount_paid' => $amount, 'currency' => 'usd',
                'status_transitions' => ['paid_at' => $paidAt ?? now()->timestamp]]]];
    }

    private function checkout($subscription, $fails = false)
    {
        $this->withCredentials();
        $plan = Plan::forceCreate(['name' => 'monthly', 'price' => 999, 'interval' => 'month', 'trial_period_days' => 7, 'description' => 'Review plan', 'statement_descriptor' => 'PIANOLIT']);
        $gateway = new \Tests\Support\FakeStripeGateway;
        $gateway->prices['monthly'] = ['id' => 'monthly', 'active' => true, 'currency' => 'usd', 'unit_amount' => 999, 'product' => 'prod_review', 'recurring' => ['interval' => 'month']];
        $gateway->nextSubscription = json_decode(json_encode($subscription), true);
        if ($fails) $gateway->failure = ['subscriptions.create', new \App\Billing\Stripe\BillingException('Review payment failure')];
        $this->app->instance(\App\Billing\Stripe\Gateway::class, $gateway);
        $id = (string) \Illuminate\Support\Str::uuid();
        $prepared = $this->postJson(route('webapp.membership.prepare', $plan), ['operation_id' => $id])->assertOk();
        $setup = explode('_secret_', $prepared->json('setup_secret'))[0];
        $gateway->confirm($setup);
        $purchase = $this->postJson(route('webapp.membership.purchase', $plan), ['operation_id' => $id, 'setup_intent' => $setup]);
        if ($purchase->status() !== 200) return $purchase;
        return $this->postJson(route('webapp.membership.complete'), ['operation_id' => $id]);
    }

    private function report()
    {
        return (new CampaignAttributionReport)->rows(now()->subDay()->toDateString())[0] ?? [
            'arrivals' => 0, 'trials' => 0, 'first_paid_subscriptions' => 0, 'first_payment_revenue' => '',
        ];
    }

    public function test_report_counts_the_supplied_youtube_link_before_any_javascript_or_opt_in()
    {
        $page = $this->get(route('webapp.pieces.show', ['piece' => 174, 'utm_source' => 'youtube.com', 'utm_medium' => 'video']))->assertOk();
        if ($directory = getenv('CAMPAIGN_REPORT_PREVIEW_DIR')) file_put_contents($directory.'/piece-without-campaign-panel.html', $page->getContent());
        $visit = DB::table('campaign_visits')->sole();
        $this->assertNull($visit->user_id);
        $this->assertNull($visit->consented_at);
        $this->assertSame(route('webapp.pieces.show', 174), $visit->landing_url);
        $page->assertCookieMissing(CampaignAttribution::VISIT_COOKIE)->assertCookieMissing(CampaignAttribution::CONSENT_COOKIE);
        $this->assertSame(1, $this->report()['arrivals']);
        $this->assertSame(0, $this->report()['trials']);
        $this->assertSame(0, $this->report()['first_paid_subscriptions']);
        $this->actingAs(create(\App\Admin::class), 'admin');
        $report = $this->get(route('admin.analytics.campaigns'))->assertOk();
        $rows = $report->viewData('campaigns');
        $this->assertSame(1, $rows->total());
        $this->assertSame('youtube.com', $rows[0]->utm_source);
        $this->assertSame('video', $rows[0]->utm_medium);
        $this->assertSame('', $rows[0]->utm_campaign);
        $this->assertSame(1, $rows[0]->arrivals);
        $this->assertSame(route('webapp.pieces.show', 174), $rows[0]->landing_page_url);
        $this->assertStringContainsString('Notturno attribution fixture', $rows[0]->landing_page_label);
        $dom = new \DOMDocument;
        @$dom->loadHTML($report->getContent());
        $links = (new \DOMXPath($dom))->query('//a[@href="'.route('webapp.pieces.show', 174).'"]');
        $this->assertSame('_blank', $links->item(0)->getAttribute('target'));
        $this->assertSame('noopener noreferrer', $links->item(0)->getAttribute('rel'));
        if ($directory = getenv('CAMPAIGN_REPORT_PREVIEW_DIR')) file_put_contents($directory.'/campaigns-anonymous.html', $report->getContent());
    }

    public function test_anonymous_first_touch_survives_refresh_and_opt_in_upgrades_it_once()
    {
        $this->withCredentials();
        $this->landing();
        $visit = DB::table('campaign_visits')->sole();
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->travel(2)->minutes();
        $this->landing();
        $later = $this->landing(['utm_source' => 'reddit.com']);
        $this->get(route('webapp.membership.pricing'))->assertOk();
        $this->assertDatabaseCount('campaign_visits', 1);
        $this->actingAs($this->user, 'web');
        $this->postJson(route('webapp.attribution.consent'), ['choice' => 'granted', 'token' => $this->token($later)])->assertOk();
        $this->assertDatabaseCount('campaign_visits', 1);
        $updated = DB::table('campaign_visits')->sole();
        $this->assertSame($visit->id, $updated->id);
        $this->assertSame($visit->created_at, $updated->created_at);
        $this->assertSame($visit->landing_url, $updated->landing_url);
        $this->assertSame('youtube.com', $updated->utm_source);
        $this->assertSame($this->user->id, $updated->user_id);
        $this->assertNotNull($updated->consented_at);
        $this->assertSame(1, $this->report()['arrivals']);
    }

    public function test_signed_in_arrivals_and_checkout_remain_unlinked_without_opt_in()
    {
        $this->actingAs($this->user, 'web');
        $this->landing();
        $visit = DB::table('campaign_visits')->sole();
        $this->checkout($this->subscription())->assertOk();
        $this->assertDatabaseHas('campaign_visits', ['id' => $visit->id, 'user_id' => null, 'consented_at' => null]);
        $this->assertDatabaseCount('campaign_subscriptions', 0);
        // Even a valid encrypted visit cookie cannot turn an unconsented row
        // into an account link without the consent endpoint.
        $this->withCookies([CampaignAttribution::VISIT_COOKIE => $visit->id, CampaignAttribution::CONSENT_COOKIE => 'v1:granted']);
        $this->get(route('webapp.pieces.show', ['piece' => 174]))->assertOk();
        $this->assertDatabaseHas('campaign_visits', ['id' => $visit->id, 'user_id' => null, 'consented_at' => null]);
    }

    public function test_first_landing_page_is_retained_across_later_tagged_destinations_and_consent()
    {
        $this->withCredentials();
        $this->landing();
        $this->withCookie(config('session.cookie'), session()->getId());
        $other = Model::withoutEvents(function () { return create(Piece::class, ['id' => 175]); });
        $other->tags()->sync($this->piece->tags->modelKeys());
        $later = $this->get(route('webapp.pieces.show', ['piece' => 175, 'utm_source' => 'reddit.com']))->assertOk();
        $this->assertDatabaseCount('campaign_visits', 1);
        $this->postJson(route('webapp.attribution.consent'), ['choice' => 'granted', 'token' => $this->token($later)])->assertOk();
        $this->assertDatabaseHas('campaign_visits', ['landing_url' => route('webapp.pieces.show', 174), 'utm_source' => 'youtube.com']);
    }

    public function test_landing_capture_omits_queries_and_redacts_non_public_route_parameters()
    {
        \Route::middleware('web')->get('/review/campaign-reset/{token}', function () { return response('<html>Reset page</html>'); });
        $this->get(config('app.url').'/review/campaign-reset/private-reset-token?utm_source=youtube.com&email=private@example.test&secret=private-query')->assertOk();
        $visit = DB::table('campaign_visits')->sole();
        $this->assertSame(config('app.url').'/review/campaign-reset/[redacted]', $visit->landing_url);
        $this->assertStringNotContainsString('private', $visit->landing_url);
        $this->assertStringNotContainsString('?', $visit->landing_url);
    }

    public function test_landing_tokens_accept_bounded_long_urls_but_reject_foreign_destinations()
    {
        $this->withCredentials();
        \Route::middleware('web')->get('/review/campaign-post/{post}', function () {
            $service = new CampaignAttribution;
            return response('<html><span data-campaign-measurement data-token="'.e($service->landingToken(request())).'"></span></html>');
        });
        $url = config('app.url').'/review/campaign-post/'.str_repeat('public-post-', 75);
        $page = $this->get($url.'?'.http_build_query(['utm_source' => 'youtube.com', 'utm_campaign' => str_repeat('a', 100)]))->assertOk();
        $token = $this->token($page);
        $this->assertGreaterThan(2048, strlen($token));
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->postJson(config('app.url').route('attribution.consent', [], false), ['choice' => 'granted', 'token' => $token])->assertOk();
        $this->assertDatabaseHas('campaign_visits', ['landing_url' => $url]);
        $data = json_decode(\Crypt::decryptString($token), true);
        $data['landing_url'] = 'https://untrusted.example/pieces/174';
        $invalid = \Crypt::encryptString(json_encode($data));
        $this->withExceptionHandling()->postJson(config('app.url').route('attribution.consent', [], false), ['choice' => 'granted', 'token' => $invalid])->assertStatus(422);
    }

    public function test_older_tokens_without_a_url_preserve_the_recorded_first_destination()
    {
        $this->withCredentials();
        $token = $this->token($this->landing());
        $data = json_decode(\Crypt::decryptString($token), true);
        unset($data['landing_url']);
        $legacy = \Crypt::encryptString(json_encode($data));
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->postJson(route('webapp.attribution.consent'), ['choice' => 'granted', 'token' => $legacy])->assertOk();
        $this->assertDatabaseCount('campaign_visits', 1);
        $this->assertDatabaseHas('campaign_visits', ['landing_url' => route('webapp.pieces.show', 174)]);
    }

    public function test_declining_removes_anonymous_arrival_and_remains_respected_on_later_links()
    {
        $this->landing();
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->post(route('webapp.attribution.consent'), ['choice' => 'denied'])->assertOk();
        $this->assertDatabaseCount('campaign_visits', 0);
        $this->withCookie(CampaignAttribution::CONSENT_COOKIE, 'v1:denied');
        $this->landing();
        $this->assertDatabaseCount('campaign_visits', 0);
    }

    public function test_anonymous_session_deduplication_stays_host_local_and_ends_with_the_session()
    {
        $this->withoutMiddleware(\App\Http\Middleware\Logs\RecordWebLog::class);
        $this->get(route('terms', ['utm_source' => 'reddit.com']))->assertOk();
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->landing();
        $this->assertDatabaseCount('campaign_visits', 2);
        $this->landing();
        $this->assertDatabaseCount('campaign_visits', 2);
        session()->invalidate();
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->landing();
        $this->assertDatabaseCount('campaign_visits', 3);
    }

    public function test_excluded_login_removes_the_previously_anonymous_arrival()
    {
        $this->landing();
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->user->email = 'elena.dare@gmail.com';
        $this->actingAs($this->user, 'web');
        $this->landing()->assertDontSee('data-campaign-measurement', false);
        $this->assertDatabaseCount('campaign_visits', 0);
        $this->assertFalse(session()->has(CampaignAttribution::ARRIVAL_SESSION));
    }

    public function test_bots_prefetch_json_errors_and_redirects_do_not_create_arrivals()
    {
        $url = route('webapp.pieces.show', ['piece' => 174, 'utm_source' => 'youtube.com', 'utm_medium' => 'video']);
        $this->withHeader('User-Agent', 'Googlebot/2.1')->get($url)->assertOk();
        $this->withHeader('User-Agent', 'Mozilla/5.0')->withHeader('Sec-Purpose', 'prefetch')->get($url)->assertOk();
        $this->flushHeaders();
        $this->getJson($url)->assertOk();
        $this->call('HEAD', $url)->assertOk();
        $this->withExceptionHandling()->get(route('webapp.pieces.show', ['piece' => 999999, 'utm_source' => 'youtube.com']))->assertNotFound();
        $this->withoutMiddleware(\App\Http\Middleware\Logs\RecordWebLog::class);
        $redirect = $this->get(rtrim(config('app.url'), '/').route('pieces.show', ['piece' => 174, 'utm_source' => 'youtube.com', 'utm_medium' => 'video'], false))->assertRedirect($url);
        $this->assertDatabaseCount('campaign_visits', 0);
        $this->get($redirect->headers->get('Location'))->assertOk();
        $this->assertDatabaseCount('campaign_visits', 1);
    }

    public function test_anonymous_arrivals_respect_admin_impersonation_privacy_and_disabled_collection()
    {
        $this->withCookie(\App\Activity\ExcludedAccounts::COOKIE, '1');
        $this->landing();
        $this->defaultCookies = [];
        $this->withSession(['impersonator' => 1]);
        $this->landing();
        session()->forget('impersonator');
        $this->actingAs(create(\App\Admin::class), 'admin');
        $this->landing();
        auth('admin')->logout();
        config(['attribution.enabled' => false]);
        $this->landing();
        $this->assertDatabaseCount('campaign_visits', 0);
    }

    public function test_anonymous_arrivals_expire_and_use_the_existing_retention_policy()
    {
        $this->landing();
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->travel(31)->days();
        $this->landing();
        $this->assertDatabaseCount('campaign_visits', 2);
        $this->travel(60)->days();
        $this->artisan('attribution:prune')->assertExitCode(0);
        $this->assertDatabaseCount('campaign_visits', 1);
    }

    public function test_arrival_storage_failure_does_not_break_the_piece_page()
    {
        DB::statement("CREATE TRIGGER campaign_arrival_fail BEFORE INSERT ON campaign_visits BEGIN SELECT RAISE(ABORT, 'simulated failure'); END");
        $handler = \Mockery::mock(\Illuminate\Contracts\Debug\ExceptionHandler::class);
        $handler->shouldReceive('report')->once()->with(\Mockery::type(\Illuminate\Database\QueryException::class));
        $this->app->instance(\Illuminate\Contracts\Debug\ExceptionHandler::class, $handler);
        $this->landing()->assertSee('Notturno attribution fixture');
        $this->assertDatabaseCount('campaign_visits', 0);
        $this->assertFalse(session()->has(CampaignAttribution::ARRIVAL_SESSION));
    }

    public function test_guest_arrival_is_automatic_but_linkage_requires_opt_in_and_valid_tokens()
    {
        $page = $this->landing()->assertDontSee('GTM-KJW7XGP')->assertDontSee('Allow measurement');
        $this->assertNotEmpty($this->token($page));
        $this->assertDatabaseCount('campaign_visits', 1);
        $this->assertDatabaseHas('campaign_visits', ['user_id' => null, 'consented_at' => null]);
        foreach (['utm_source' => 'someone@example.com', 'utm_campaign' => '<script>alert(1)</script>', 'utm_content' => ['description']] as $field => $value) {
            $this->assertSame('', $this->token($this->landing([$field => $value])));
        }
        $this->assertNull($this->attribution->campaignFromToken('forged'));
        $token = $this->token($page);
        $this->travel(11)->minutes();
        $this->assertNull($this->attribution->campaignFromToken($token));
        $this->withExceptionHandling()->post(route('webapp.attribution.consent'), ['choice' => 'granted', 'token' => $token])->assertStatus(422);
        $this->assertDatabaseCount('campaign_visits', 1);
        $this->assertDatabaseHas('campaign_visits', ['user_id' => null, 'consented_at' => null]);
    }

    public function test_ordinary_search_has_no_campaign_control_in_its_initial_html()
    {
        foreach ([null, $this->user] as $user) {
            if ($user) $this->actingAs($user, 'web');
            $this->get(route('webapp.search.results', ['search' => 'notturno']))->assertOk()
                ->assertSee('id="pieces-list"', false)
                ->assertDontSee('data-campaign-measurement', false)
                ->assertDontSee('Campaign link measurement')
                ->assertDontSee('js/views/campaign-attribution.js');
        }
        $this->assertDatabaseCount('campaign_visits', 0);
    }

    public function test_public_and_webapp_layouts_never_render_a_campaign_preference_panel()
    {
        $this->withoutMiddleware(\App\Http\Middleware\Logs\RecordWebLog::class);
        $public = rtrim(config('app.url'), '/');
        $this->get($public.'/login?utm_source=youtube.com')->assertOk()
            ->assertDontSee('Campaign link measurement')->assertDontSee('campaign-measurement-choice')
            ->assertDontSee('Allow measurement')->assertDontSee('data-campaign-choice');
        foreach ([null, $this->user] as $user) {
            if ($user) $this->actingAs($user, 'web');
            foreach ([[], ['utm_source' => 'youtube.com', 'utm_medium' => 'video']] as $tags) {
                $pages = [
                    $this->get(route('webapp.pieces.show', ['piece' => 174] + $tags))->assertOk(),
                    $this->get($public.route('terms', $tags, false))->assertOk(),
                ];
                foreach ($pages as $page) {
                    $page->assertDontSee('Campaign link measurement')->assertDontSee('campaign-measurement-choice')
                        ->assertDontSee('Allow measurement')->assertDontSee('data-campaign-choice')
                        ->assertDontSee('js/views/campaign-attribution.js');
                }
            }
        }
    }

    public function test_tagged_search_counts_anonymous_arrival_without_visible_campaign_controls()
    {
        $page = $this->get(route('webapp.search.results', [
            'search' => 'notturno', 'utm_source' => 'youtube.com',
        ]))->assertOk()->assertDontSee('Campaign link measurement')->assertDontSee('Allow measurement');
        $token = $this->token($page);
        $this->assertNotEmpty($token);
        $dom = new \DOMDocument;
        @$dom->loadHTML($page->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertTrue($xpath->query('//*[@data-campaign-measurement]')->item(0)->hasAttribute('hidden'));
        $this->assertSame(0, $xpath->query('//*[@id="campaign-measurement-choice"]')->length);
        $this->assertDatabaseCount('campaign_visits', 1);
        $this->assertDatabaseHas('campaign_visits', ['user_id' => null, 'consented_at' => null]);
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->post(route('webapp.attribution.consent'), ['choice' => 'granted', 'token' => $token])->assertOk();
        $this->assertDatabaseCount('campaign_visits', 1);
    }

    public function test_tagged_search_only_acknowledges_a_previously_granted_choice()
    {
        foreach (['granted', 'denied'] as $choice) {
            $this->withCookie(CampaignAttribution::CONSENT_COOKIE, 'v1:'.$choice);
            $page = $this->get(route('webapp.search.results', [
                'search' => 'notturno', 'utm_source' => 'youtube.com',
            ]))->assertOk()->assertDontSee('Campaign link measurement');
            if ($choice === 'granted') $page->assertSee('js/views/campaign-attribution.js');
            else $page->assertDontSee('js/views/campaign-attribution.js');
            $this->assertNotEmpty($this->token($page));
            $dom = new \DOMDocument;
            @$dom->loadHTML($page->getContent());
            $root = (new \DOMXPath($dom))->query('//*[@data-campaign-measurement]')->item(0);
            $this->assertTrue($root->hasAttribute('hidden'));
            $this->assertSame($choice, $root->getAttribute('data-consent'));
        }
        $this->assertDatabaseCount('campaign_visits', 1);
    }

    public function test_refusal_refresh_and_direct_navigation_do_not_add_arrivals()
    {
        $this->post(route('webapp.attribution.consent'), ['choice' => 'denied'])->assertOk();
        $this->assertDatabaseCount('campaign_visits', 0);
        [$visit, $token, $response] = $this->grant();
        // Duplicate acknowledgement, including a retry before the cookie arrives.
        $this->defaultCookies = [config('session.cookie') => session()->getId()];
        $this->post(route('webapp.attribution.consent'), ['choice' => 'granted', 'token' => $token])->assertOk();
        $this->withCookies([CampaignAttribution::CONSENT_COOKIE => 'v1:granted', CampaignAttribution::VISIT_COOKIE => $visit->id]);
        $this->landing();
        $this->get(route('webapp.membership.pricing'))->assertOk();
        $this->assertDatabaseCount('campaign_visits', 1);
        foreach ($response->headers->getCookies() as $cookie) {
            if (in_array($cookie->getName(), [CampaignAttribution::CONSENT_COOKIE, CampaignAttribution::VISIT_COOKIE], true)) {
                $this->assertNull($cookie->getDomain());
                $this->assertTrue($cookie->isHttpOnly());
                $this->assertSame('lax', $cookie->getSameSite());
                $this->assertNotSame($visit->id, $cookie->getValue());
            }
        }
    }

    public function test_source_only_links_work_for_any_piece_and_new_sources_without_configuration()
    {
        $this->withCredentials();
        $other = Model::withoutEvents(function () { return create(Piece::class, ['id' => 175]); });
        $other->tags()->sync($this->piece->tags->modelKeys());
        foreach (['youtube.com', 'chatgpt.com', 'reddit.com', 'new-source.example', 'newsletter'] as $source) {
            $page = $this->get(route('webapp.pieces.show', ['piece' => $other->id, 'utm_source' => $source]))->assertOk();
            $token = $this->token($page);
            $this->assertNotEmpty($token);
            $this->assertDatabaseCount('campaign_visits', 1);
            $this->assertDatabaseHas('campaign_visits', ['user_id' => null, 'consented_at' => null]);
            $this->withCookie(config('session.cookie'), session()->getId());
            $this->postJson(route('webapp.attribution.consent'), ['choice' => 'granted', 'token' => $token, 'user_id' => $this->user->id])->assertOk();
            $this->assertDatabaseHas('campaign_visits', ['utm_source' => $source, 'utm_medium' => '', 'utm_campaign' => '', 'utm_content' => '', 'user_id' => null]);
            DB::table('campaign_visits')->delete();
            $this->defaultCookies = [];
        }
    }

    public function test_source_medium_and_campaign_can_each_be_used_alone_or_in_any_combination()
    {
        $this->withCredentials();
        $fields = ['utm_source' => 'youtube.com', 'utm_medium' => 'video', 'utm_campaign' => 'autumn_repertoire'];
        for ($combination = 1; $combination < 8; $combination++) {
            $parameters = [];
            foreach (array_keys($fields) as $index => $field) {
                if ($combination & (1 << $index)) $parameters[$field] = $fields[$field];
            }
            $page = $this->get(route('webapp.pieces.show', ['piece' => $this->piece->id] + $parameters))->assertOk();
            $token = $this->token($page);
            $this->assertNotEmpty($token, json_encode($parameters));
            $this->assertDatabaseCount('campaign_visits', 1);
            $this->assertDatabaseHas('campaign_visits', ['user_id' => null, 'consented_at' => null]);
            $this->withCookie(config('session.cookie'), session()->getId());
            $this->postJson(route('webapp.attribution.consent'), ['choice' => 'granted', 'token' => $token])->assertOk();
            $expected = $parameters + array_fill_keys(CampaignParameters::FIELDS, '');
            $this->assertDatabaseHas('campaign_visits', $expected + ['user_id' => null]);
            $this->assertDatabaseCount('campaign_visits', 1);
            DB::table('campaign_visits')->delete();
            $this->defaultCookies = [];
        }
    }

    public function test_sources_normalize_and_malformed_parameters_never_get_a_landing_token()
    {
        foreach (['youtube' => 'youtube.com', 'www.YouTube.COM' => 'youtube.com', 'CHATGPT' => 'chatgpt.com', 'Reddit' => 'reddit.com', 'New-Site.Example' => 'new-site.example'] as $source => $expected) {
            $parameters = CampaignParameters::fromQuery(['utm_source' => $source, 'utm_campaign' => 'New Piece']);
            $this->assertSame($expected, $parameters['utm_source']);
            $this->assertSame('new piece', $parameters['utm_campaign']);
        }
        foreach ([[], ['utm_source' => ''], ['utm_content' => 'description'], ['utm_source' => 'www.'], ['utm_source' => 'https://youtube.com'], ['utm_source' => ['youtube.com']],
            ['utm_source' => 'someone@example.com'], ['utm_source' => 'two sources'], ['utm_source' => str_repeat('a', 101)],
            ['utm_source' => 'valid.com', 'utm_medium' => ['video']], ['utm_source' => 'valid.com', 'utm_content' => '<script>'],
        ] as $parameters) {
            $this->assertNull(CampaignParameters::fromQuery($parameters));
            $this->assertSame('', $this->token($this->get(route('webapp.pieces.show', ['piece' => $this->piece->id] + $parameters))->assertOk()));
        }
        $this->assertDatabaseCount('campaign_visits', 0);
    }

    public function test_public_website_links_are_measured_on_their_own_host_and_session()
    {
        $this->withCredentials();
        $this->withoutMiddleware(\App\Http\Middleware\Logs\RecordWebLog::class);
        $page = $this->get(route('terms', ['utm_source' => 'reddit.com']))->assertOk()->assertDontSee('Campaign link measurement');
        $token = $this->token($page);
        $this->assertNotEmpty($token);
        $this->assertStringContainsString('data-url="/attribution/consent"', $page->getContent());
        $this->withCookie(config('session.cookie'), session()->getId());
        // Even a genuine token from the public host cannot be replayed on my.*.
        $this->withExceptionHandling()->postJson(route('webapp.attribution.consent'), ['choice' => 'granted', 'token' => $token])->assertStatus(422);
        $this->postJson(config('app.url').route('attribution.consent', [], false), ['choice' => 'granted', 'token' => $token])->assertOk();
        $this->assertDatabaseHas('campaign_visits', ['utm_source' => 'reddit.com', 'user_id' => null]);
    }

    public function test_public_piece_redirects_preserve_campaign_tags_for_the_web_app_landing()
    {
        $this->withoutMiddleware(\App\Http\Middleware\Logs\RecordWebLog::class);
        foreach ([['utm_source' => 'youtube.com'], ['utm_medium' => 'video'], ['utm_campaign' => 'any_piece'],
            ['utm_source' => 'youtube.com', 'utm_medium' => 'video', 'utm_campaign' => 'any_piece', 'utm_content' => 'description'],
        ] as $tags) {
            $parameters = ['piece' => $this->piece->id] + $tags;
            $this->get(route('pieces.show', $parameters))->assertRedirect(route('webapp.pieces.show', $parameters));
        }
        $this->assertDatabaseCount('campaign_visits', 0);
    }

    public function test_a_signed_token_cannot_be_replayed_from_another_browser_session()
    {
        $this->withCredentials();
        $token = $this->token($this->landing());
        session()->invalidate();
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->withExceptionHandling()->postJson(route('webapp.attribution.consent'), ['choice' => 'granted', 'token' => $token])->assertStatus(422);
        $this->assertDatabaseCount('campaign_visits', 1);
        $this->assertDatabaseHas('campaign_visits', ['user_id' => null, 'consented_at' => null]);
    }

    public function test_requests_without_a_web_session_do_not_offer_campaign_measurement()
    {
        $request = \Illuminate\Http\Request::create(route('webapp.pieces.show', ['piece' => $this->piece->id, 'utm_source' => 'youtube.com']));
        $this->assertFalse($this->attribution->available($request));
        $this->assertNull($this->attribution->landingToken($request));
    }

    public function test_first_eligible_source_is_retained_instead_of_replaced_by_later_campaigns()
    {
        $this->withCredentials();
        [$visit] = $this->grant();
        $page = $this->landing(['utm_source' => 'reddit.com', 'utm_campaign' => 'another_campaign']);
        $this->postJson(route('webapp.attribution.consent'), ['choice' => 'granted', 'token' => $this->token($page)])->assertOk();
        $this->assertDatabaseCount('campaign_visits', 1);
        $this->assertDatabaseHas('campaign_visits', ['id' => $visit->id, 'utm_source' => 'youtube.com', 'utm_campaign' => 'respighi_notturno']);
    }

    public function test_consent_endpoints_keep_real_csrf_and_excluded_accounts_never_get_campaign_tokens()
    {
        foreach (['arthurvillar@gmail.com', 'elena.dare@gmail.com', 'arthurvilar@gmail.com'] as $email) {
            $this->user->email = $email;
            $this->actingAs($this->user, 'web');
            $this->landing(['utm_source' => 'new-source.example'])->assertDontSee('data-campaign-measurement', false);
            $this->withExceptionHandling()->postJson(route('webapp.attribution.consent'), ['choice' => 'granted'])->assertNotFound();
        }
        auth('web')->logout();
        $this->app->bind(\App\Http\Middleware\VerifyCsrfToken::class, function ($app) {
            return new class($app, $app['encrypter']) extends \App\Http\Middleware\VerifyCsrfToken {
                protected function runningUnitTests() { return false; }
            };
        });
        $this->postJson(route('webapp.attribution.consent'), ['choice' => 'granted'])->assertStatus(419);
        $this->postJson(route('attribution.consent'), ['choice' => 'granted'])->assertStatus(419);
        $this->assertDatabaseCount('campaign_visits', 0);
    }

    public function test_signup_claims_guest_touch_and_preserves_it_through_session_regeneration()
    {
        [$visit] = $this->grant();
        $this->post(rtrim(route('webapp.discover'), '/').'/register', [
            'first_name' => 'Campaign', 'last_name' => 'Listener', 'email' => 'campaign@example.test',
            'password' => 'review-password', 'password_confirmation' => 'review-password', 'origin' => 'webapp',
            'user_id' => $this->user->id,
        ])->assertRedirect(route('webapp.welcome'));
        $newUser = auth('web')->user();
        $this->assertNotSame($this->user->id, $newUser->id);
        $this->assertDatabaseHas('campaign_visits', ['id' => $visit->id, 'user_id' => $newUser->id]);
        $this->get(route('webapp.membership.pricing'))->assertOk();
        $this->assertDatabaseCount('campaign_visits', 1);
        $this->assertDatabaseCount('campaign_subscriptions', 0);
    }

    public function test_existing_account_login_claims_the_touch_and_other_accounts_cannot_reuse_it()
    {
        [$visit] = $this->grant();
        $this->post(rtrim(route('webapp.discover'), '/').'/login', ['email' => $this->user->email, 'password' => 'review-password'])->assertRedirect();
        $this->assertDatabaseHas('campaign_visits', ['id' => $visit->id, 'user_id' => $this->user->id]);
        $other = Model::withoutEvents(function () { return create(User::class)->setAppends(['full_name']); });
        $this->actingAs($other, 'web')->get(route('webapp.membership.pricing'))->assertOk();
        $this->assertDatabaseHas('campaign_visits', ['id' => $visit->id, 'user_id' => $this->user->id]);
        $this->assertNull($this->attribution->visit(request()));
        $this->assertDatabaseCount('campaign_subscriptions', 0);
    }

    public function test_checkout_records_only_provider_confirmed_trial_and_exact_subscription()
    {
        $this->grant();
        $this->actingAs($this->user, 'web');
        $this->get(route('webapp.membership.pricing'))->assertOk();
        $this->assertSame(0, $this->report()['trials']);
        $this->checkout($this->subscription())->assertOk()->assertJson(['redirect' => route('webapp.discover')]);
        $this->assertDatabaseHas('campaign_subscriptions', ['stripe_subscription_id' => 'sub_first', 'stripe_customer_id' => 'cus_first', 'billing_account' => 'us']);
        $this->assertSame(1, $this->report()['trials']);
        $this->assertSame(0, $this->report()['first_paid_subscriptions']);
        Cache::put('app.discover'.\App\Api\Api::WEB_DISCOVER_CACHE_SUFFIX, collect([
            ['title' => 'Latest pieces', 'row' => 'gallery', 'type' => 'piece', 'content' => []],
        ]), 60);
        $this->get(route('webapp.discover'))->assertOk()->assertSee('id="membership-confirmation-modal"', false);
        $this->get(route('webapp.discover'))->assertOk()->assertDontSee('id="membership-confirmation-modal"', false);
        $this->assertSame(1, $this->report()['trials']);
    }

    public function test_failed_checkout_does_not_record_a_trial_or_subscription()
    {
        $this->grant();
        $this->actingAs($this->user, 'web');
        $this->checkout($this->subscription(), true)->assertStatus(422);
        $this->assertDatabaseCount('campaign_subscriptions', 0);
        $this->assertDatabaseCount('memberships', 0);
    }

    public function test_positive_invoice_before_checkout_is_reconciled_and_renewals_never_add_conversions()
    {
        $this->grant();
        $this->actingAs($this->user, 'web');
        $receipts = new StripePaymentReceipts;
        $renewal = $this->invoice('in_renewal', 'sub_first', 999, now()->addMonth()->timestamp);
        $first = $this->invoice();
        $receipts->invoiceSucceeded($renewal); // Delivered out of time order.
        $receipts->invoiceSucceeded($first);   // Delivered before local subscription binding.
        $receipts->invoiceSucceeded($first);
        $first['id'] = 'evt_secondDelivery';
        $receipts->invoiceSucceeded($first);
        $this->assertSame(0, $this->report()['first_paid_subscriptions']);
        $this->checkout($this->subscription())->assertOk();
        $this->assertDatabaseCount('stripe_payment_receipts', 2);
        $this->assertSame(1, $this->report()['first_paid_subscriptions']);
        $this->assertSame('USD 9.99', $this->report()['first_payment_revenue']);
        // Customer mismatch and shop invoices must never enter this funnel.
        $receipts->invoiceSucceeded($this->invoice('in_other', 'sub_first', 8999, now()->subDay()->timestamp, 'cus_other'));
        $receipts->invoiceSucceeded($this->invoice('in_shop', null));
        $this->assertSame('USD 9.99', $this->report()['first_payment_revenue']);
    }

    public function test_zero_failed_manual_and_unattributed_invoices_are_not_paid_conversions()
    {
        $receipts = new StripePaymentReceipts;
        $receipts->invoiceSucceeded($this->invoice('in_zero', 'sub_first', 0));
        $failed = $this->invoice('in_failed');
        $failed['data']['object']['paid'] = false;
        $receipts->invoiceSucceeded($failed);
        // Dispatcher has no paid/manual/failed conversion handler.
        $this->assertFalse(method_exists(StripeWebhooks::class, 'whenInvoicePaid'));
        $this->assertFalse(method_exists(StripeWebhooks::class, 'whenInvoicePaymentFailed'));
        $receipts->invoiceSucceeded($this->invoice('in_unattributed'));
        $this->assertSame(0, $this->report()['first_paid_subscriptions']);
        $this->assertDatabaseCount('stripe_payment_receipts', 1);
    }

    public function test_immediate_active_subscription_counts_paid_without_inventing_a_trial()
    {
        $this->grant();
        $this->actingAs($this->user, 'web');
        $this->checkout($this->subscription('sub_first', 'active'))->assertOk();
        (new StripePaymentReceipts)->invoiceSucceeded($this->invoice());
        $this->assertSame(0, $this->report()['trials']);
        $this->assertSame(1, $this->report()['first_paid_subscriptions']);
    }

    public function test_charge_retries_are_unique_and_failure_rolls_back_the_marker()
    {
        $receipts = new StripePaymentReceipts;
        $payload = ['data' => ['object' => ['id' => 'ch_first']]];
        $record = function () { Payment::create(['charge_id' => 'ch_first', 'amount' => 999, 'refund' => 0]); };
        $receipts->oncePerCharge($payload, $record);
        $receipts->oncePerCharge($payload, $record);
        $this->assertDatabaseCount('payments', 1);
        try {
            $receipts->oncePerCharge(['data' => ['object' => ['id' => 'ch_retry']]], function () { throw new \RuntimeException('review failure'); });
            $this->fail('Expected failure.');
        } catch (\RuntimeException $exception) { $this->assertSame('review failure', $exception->getMessage()); }
        $this->assertDatabaseMissing('stripe_payment_receipts', ['stripe_object_id' => 'ch_retry']);
        Payment::create(['charge_id' => 'ch_historic', 'amount' => 999, 'refund' => 0]);
        $receipts->oncePerCharge(['data' => ['object' => ['id' => 'ch_historic']]], function () { $this->fail('Historic charge recorded twice.'); });
        $this->assertDatabaseCount('payments', 2);
    }

    public function test_withdrawal_removes_campaign_links_but_preserves_billing_and_logout_clears_cookies()
    {
        [$visit] = $this->grant();
        $this->actingAs($this->user, 'web');
        $this->checkout($this->subscription())->assertOk();
        (new StripePaymentReceipts)->invoiceSucceeded($this->invoice());
        $this->post(route('webapp.attribution.consent'), ['choice' => 'denied'])->assertOk();
        $this->assertDatabaseCount('campaign_visits', 0);
        $this->assertDatabaseCount('campaign_subscriptions', 0);
        $this->assertDatabaseCount('stripe_payment_receipts', 1);
        $this->assertDatabaseCount('memberships', 1);
        $this->post(route('webapp.logout'))->assertRedirect()->assertCookieExpired(CampaignAttribution::VISIT_COOKIE)->assertCookieExpired(CampaignAttribution::CONSENT_COOKIE);
    }

    public function test_expired_touch_and_disabled_feature_do_not_attribute_or_alter_billing()
    {
        [$visit] = $this->grant();
        DB::table('campaign_visits')->where('id', $visit->id)->update(['expires_at' => now()->subMinute()]);
        $this->actingAs($this->user, 'web');
        $this->checkout($this->subscription())->assertOk();
        $this->assertDatabaseCount('campaign_subscriptions', 0);
        config(['attribution.enabled' => false]);
        $this->landing()->assertDontSee('data-campaign-measurement', false);
        (new StripePaymentReceipts)->invoiceSucceeded($this->invoice());
        $this->assertDatabaseCount('stripe_payment_receipts', 0);
        $this->withExceptionHandling()->post(route('webapp.attribution.consent'), ['choice' => 'granted'])->assertNotFound();
        $this->assertDatabaseCount('memberships', 1);
    }

    public function test_signed_invoice_dispatch_records_one_receipt_and_forged_payloads_cannot_record_it()
    {
        config(['services.stripe.webhook.secret' => 'whsec_review']);
        $body = json_encode($this->invoice());
        $timestamp = time();
        $request = \Illuminate\Http\Request::create('/webhooks/stripe', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$body, 'whsec_review'),
        ], $body);
        $controller = new \App\Http\Controllers\Webhooks\StripeWebhookController;
        $this->assertSame(200, $controller($request)->getStatusCode());
        $this->assertSame(200, $controller($request)->getStatusCode());
        $this->assertDatabaseCount('stripe_payment_receipts', 1);
        $request->headers->set('Stripe-Signature', 't='.$timestamp.',v1=forged');
        try { $controller($request); $this->fail('Forged invoice accepted.'); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) { $this->assertSame(400, $exception->getStatusCode()); }
        $this->assertDatabaseCount('stripe_payment_receipts', 1);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_unique_database_constraints_and_bounded_retention()
    {
        [$visit] = $this->grant();
        $this->actingAs($this->user, 'web');
        $this->checkout($this->subscription())->assertOk();
        (new StripePaymentReceipts)->invoiceSucceeded($this->invoice());
        $record = (array) DB::table('campaign_subscriptions')->first();
        unset($record['id']);
        $this->assertSame(0, DB::table('campaign_subscriptions')->insertOrIgnore($record));
        $receipt = (array) DB::table('stripe_payment_receipts')->first();
        unset($receipt['id']);
        $this->assertSame(0, DB::table('stripe_payment_receipts')->insertOrIgnore($receipt));
        $this->travel(91)->days();
        $this->artisan('attribution:prune')->assertExitCode(0);
        $this->assertDatabaseCount('campaign_visits', 0);
        $this->assertDatabaseCount('campaign_subscriptions', 0);
        $this->assertDatabaseCount('stripe_payment_receipts', 0);
        $this->assertDatabaseCount('memberships', 1);
    }
}
