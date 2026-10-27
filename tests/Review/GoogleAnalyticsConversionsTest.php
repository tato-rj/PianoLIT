<?php

namespace Tests\Review;

use App\{Admin, User};
use App\Analytics\GoogleAnalyticsConversions as Google;
use App\Billing\Stripe\Gateway;
use App\Services\{CampaignAttribution, CampaignConversions, CampaignParameters, StripePaymentReceipts};
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Crypt, DB, Http, Route};
use Illuminate\Support\Str;
use Tests\Support\FakeStripeGateway;

class GoogleAnalyticsConversionsTest extends GamesTestCase
{
    private $gateway, $user, $visit, $operation, $subscription;

    public function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-27 18:00:00', 'UTC'));
        config(['attribution.enabled' => false, 'attribution.conversion_links_enabled' => true,
            'services.google_analytics.api_secret' => 'review-secret', 'services.google_analytics.api_secret_file' => null]);
        $this->gateway = new FakeStripeGateway;
        $this->app->instance(Gateway::class, $this->gateway);
        $this->user = Model::withoutEvents(fn () => create(User::class));
        $this->actingAs($this->user, 'web');
        $this->withCredentials();
        Route::middleware('web')->get('/review/campaign-conversion', function () {
            return response('<html>'.view('webapp.components.campaign-attribution')->render().'</html>');
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        app()->instance('env', 'testing');
        parent::tearDown();
    }

    private function fixture(array $subscription = [], array $visit = []): void
    {
        $this->visit = (string) Str::uuid();
        $parameters = CampaignParameters::fromQuery(['utm_source' => 'pianolit', 'utm_medium' => 'email', 'utm_campaign' => 'freepick', 'utm_content' => 'explore_piece']);
        DB::table('campaign_visits')->insert(array_replace($parameters + ['id' => $this->visit,
            'campaign' => CampaignParameters::key($parameters), 'user_id' => $this->user->id,
            'created_at' => now()->subHour(), 'consented_at' => now()->subHour(), 'expires_at' => now()->addDays(30),
            'landing_url' => 'http://my.localhost/pieces/845',
            'google_context' => Crypt::encryptString(json_encode(Google::browserContext(['client_id' => '123456.7890', 'session_id' => (string) now()->subMinute()->timestamp])))], $visit));
        $this->subscription = array_replace(['id' => 'sub_review', 'customer' => 'cus_review', 'status' => 'trialing', 'livemode' => true,
            'created' => now()->timestamp, 'trial_start' => now()->timestamp, 'trial_end' => now()->addDays(14)->timestamp], $subscription);
        $this->gateway->subscriptions['sub_review'] = $this->subscription;
        $this->operation = (string) Str::uuid();
        DB::table('stripe_billing_operations')->insert(['id' => $this->operation, 'user_id' => $this->user->id, 'kind' => 'purchase', 'billing_account' => 'us',
            'customer_id' => 'cus_review', 'subscription_id' => 'sub_review', 'plan' => 'monthly', 'state' => 'confirming', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('campaign_checkout_contexts')->insert(['operation_id' => $this->operation, 'visit_id' => $this->visit, 'created_at' => now('UTC')]);
    }

    private function invoice(string $id = 'in_first', int $amount = 799, ?int $at = null): array
    {
        return ['id' => $id, 'customer' => 'cus_review', 'subscription' => 'sub_review', 'paid' => true, 'status' => 'paid', 'livemode' => true,
            'amount_paid' => $amount, 'currency' => 'usd', 'status_transitions' => ['paid_at' => $at ?? now()->timestamp]];
    }

    private function receipt(array $invoice): void
    {
        (new StripePaymentReceipts)->invoiceSucceeded(['type' => 'invoice.payment_succeeded', 'created' => now()->timestamp, 'data' => ['object' => $invoice]]);
    }

    private function reconcile(): void { (new CampaignConversions)->reconcile(); }
    private function send(): int { app()->instance('env', 'production'); return (new Google)->sendPending(); }
    private function body(): array { return Http::recorded()->last()[0]->data(); }

    public function test_tagged_navigation_stages_only_a_host_local_session_touch_without_sql_traffic()
    {
        $this->get('http://localhost/review/campaign-conversion?utm_source=youtube&utm_campaign=launch')->assertOk();
        $this->withCookie(config('session.cookie'), session()->getId());
        $page = $this->get('http://localhost/review/campaign-conversion')->assertOk();
        preg_match('/data-token="([^"]+)"/', $page->getContent(), $match);
        $touch = json_decode(Crypt::decryptString(html_entity_decode($match[1])), true);
        $this->assertSame('launch', $touch['parameters']['utm_campaign']);
        $this->assertSame('http://localhost/review/campaign-conversion', $touch['landing_url']);
        $this->get('http://learn.localhost/review/campaign-conversion')->assertOk()->assertDontSee('data-campaign-measurement');
        $this->assertDatabaseCount('campaign_visits', 0);
        $this->assertDatabaseCount('google_analytics_conversions', 0);
        Http::assertNothingSent();
    }

    public function test_existing_permission_captures_only_google_ids_and_checkout_operation_ownership()
    {
        $this->fixture();
        $this->withCookies([CampaignAttribution::CONSENT_COOKIE => 'v1:granted', CampaignAttribution::VISIT_COOKIE => $this->visit]);
        $this->postJson('http://localhost/attribution/consent', ['choice' => 'granted', 'client_id' => '765.432', 'session_id' => (string) now()->timestamp,
            'email' => 'never-export@example.com', 'user_id' => 123])->assertOk();
        $context = json_decode(Crypt::decryptString(DB::table('campaign_visits')->sole()->google_context), true);
        $this->assertSame(['client_id', 'session_id', 'observed_at'], array_keys($context));
        $this->assertSame('765.432', $context['client_id']);
        DB::table('campaign_checkout_contexts')->delete();
        Route::middleware('web')->post('/review/link-checkout', function () {
            (new CampaignAttribution)->checkout(request(), request()->user('web'), request('operation_id'));
            return response()->noContent();
        });
        DB::table('stripe_billing_operations')->where('id', $this->operation)->update(['user_id' => $this->user->id + 500]);
        $this->postJson('http://localhost/review/link-checkout', ['operation_id' => $this->operation])->assertNoContent();
        $this->assertDatabaseCount('campaign_checkout_contexts', 0);
        DB::table('stripe_billing_operations')->where('id', $this->operation)->update(['user_id' => $this->user->id]);
        $this->postJson('http://localhost/review/link-checkout', ['operation_id' => $this->operation])->assertNoContent();
        $this->assertDatabaseCount('campaign_checkout_contexts', 1);
    }

    public function test_confirmed_trial_recovers_without_browser_finish_and_sends_once_without_personal_identity()
    {
        $this->fixture(); $this->reconcile(); $this->reconcile();
        $this->assertDatabaseCount('campaign_subscriptions', 1);
        $this->assertDatabaseCount('google_analytics_conversions', 1);
        $this->assertSame(1, $this->send());
        $body = $this->body(); $params = $body['events'][0]['params'];
        $this->assertSame('membership_trial_started', $body['events'][0]['name']);
        $this->assertSame('123456.7890', $body['client_id']);
        $this->assertSame('freepick', $params['membership_campaign']);
        $this->assertSame('email', $params['membership_medium']);
        $this->assertSame((string) now()->subMinute()->timestamp, $params['session_id']);
        $this->assertSame(now()->timestamp * 1000000, $body['timestamp_micros']);
        $this->assertSame(['ad_user_data' => 'DENIED', 'ad_personalization' => 'DENIED'], $body['consent']);
        foreach ([$this->user->email, 'cus_review', 'sub_review', $this->operation, $this->visit] as $private) $this->assertStringNotContainsString($private, json_encode($body));
        $this->assertArrayNotHasKey('user_id', $body);
        $this->assertArrayNotHasKey('engagement_time_msec', $params);
        $this->reconcile(); $this->assertSame(0, $this->send()); Http::assertSentCount(1);
    }

    public function test_first_positive_payment_is_chosen_from_provider_history_not_webhook_order_or_renewal()
    {
        $this->fixture(['status' => 'active', 'trial_start' => null, 'trial_end' => null]);
        $renewal = $this->invoice('in_renewal', 999, now()->addMinute()->timestamp);
        $this->receipt($renewal); // Webhook before the campaign/subscription link.
        $this->gateway->invoices = [$renewal, $this->invoice('in_first', 799), $this->invoice('in_free', 0)];
        $this->reconcile(); $this->assertSame(1, $this->send());
        $body = $this->body(); $params = $body['events'][0]['params'];
        $this->assertSame('purchase', $body['events'][0]['name']);
        $this->assertSame('first_payment', $params['membership_conversion']);
        $this->assertSame(7.99, $params['value']); $this->assertSame('USD', $params['currency']);
        $this->assertStringStartsWith('membership_', $params['transaction_id']);
        $this->receipt($renewal); $this->receipt($this->invoice()); $this->reconcile();
        $this->assertSame(0, $this->send()); Http::assertSentCount(1);
        $this->assertDatabaseCount('google_analytics_conversions', 1);
        $this->assertSame('us', $this->gateway->callsTo('invoices', 'all')[0]['account']);
    }

    public function test_delayed_payment_uses_actual_time_and_saved_campaign_without_a_fabricated_session()
    {
        $this->fixture(['created' => now()->subDays(14)->timestamp, 'trial_start' => null, 'trial_end' => null, 'status' => 'active'],
            ['created_at' => now()->subDays(15), 'consented_at' => now()->subDays(15),
                'google_context' => Crypt::encryptString(json_encode(['client_id' => '123.456', 'session_id' => (string) now()->subDays(14)->timestamp, 'observed_at' => now()->subDays(14)->timestamp]))]);
        $this->gateway->invoices = [$this->invoice()]; $this->receipt($this->invoice()); $this->reconcile();
        $this->assertSame(1, $this->send());
        $body = $this->body(); $this->assertSame(now()->timestamp * 1000000, $body['timestamp_micros']);
        $this->assertArrayNotHasKey('session_id', $body['events'][0]['params']);
        $this->assertSame('freepick', $body['events'][0]['params']['membership_campaign']);
    }

    public function test_renewal_cannot_become_a_new_payment_when_original_payment_is_outside_ga_window()
    {
        $this->fixture(['created' => now()->subDays(35)->timestamp, 'trial_start' => null, 'trial_end' => null, 'status' => 'active'],
            ['created_at' => now()->subDays(36), 'consented_at' => now()->subDays(36)]);
        $renewal = $this->invoice('in_renewal'); $this->receipt($renewal);
        $this->gateway->invoices = [$renewal, $this->invoice('in_first', 799, now()->subDays(35)->timestamp)];
        $this->reconcile(); $this->assertSame(0, $this->send()); Http::assertNothingSent();
        $this->assertDatabaseHas('google_analytics_conversions', ['state' => 'skipped']);
    }

    public function test_test_mode_unowned_and_excluded_subscriptions_never_export()
    {
        $this->fixture(['livemode' => false]); $this->reconcile(); $this->assertDatabaseCount('google_analytics_conversions', 0);
        $this->gateway->subscriptions['sub_review']['livemode'] = true;
        DB::table('campaign_checkout_contexts')->update(['linked_at' => null]);
        DB::table('stripe_billing_operations')->update(['billing_account' => 'swiss']);
        $this->gateway->intercept = function ($call) { if ($call['account'] === 'swiss') throw new \RuntimeException('Wrong account'); };
        $this->reconcile(); $this->assertDatabaseCount('google_analytics_conversions', 0);
        $this->gateway->intercept = null;
        DB::table('stripe_billing_operations')->update(['billing_account' => 'us']);
        $this->user->update(['email' => 'elena.dare@gmail.com']);
        $this->reconcile(); $this->assertSame(0, $this->send()); Http::assertNothingSent();
    }

    public function test_wrong_customer_or_unconfirmed_incomplete_trial_cannot_produce_a_trial()
    {
        $this->fixture(); $this->gateway->subscriptions['sub_review']['customer'] = 'cus_other';
        $this->reconcile(); $this->assertDatabaseCount('campaign_subscriptions', 0);
        $this->gateway->subscriptions['sub_review'] = array_replace($this->subscription, ['status' => 'incomplete']);
        $this->reconcile(); $this->assertDatabaseCount('campaign_subscriptions', 1);
        $this->assertDatabaseCount('google_analytics_conversions', 0);
    }

    public function test_withdrawal_cascades_pending_events_and_frozen_payloads()
    {
        $this->fixture(); $this->reconcile();
        $this->withCookies([CampaignAttribution::CONSENT_COOKIE => 'v1:granted', CampaignAttribution::VISIT_COOKIE => $this->visit]);
        $this->postJson('http://localhost/attribution/consent', ['choice' => 'denied'])->assertOk();
        foreach (['campaign_visits', 'campaign_subscriptions', 'campaign_checkout_contexts', 'google_analytics_conversions'] as $table) $this->assertDatabaseCount($table, 0);
        $this->assertSame(0, $this->send()); Http::assertNothingSent();
    }

    public function test_secret_absence_nonproduction_and_invalid_google_identity_do_not_send()
    {
        $this->fixture(); $this->reconcile();
        $this->assertSame(0, (new Google)->sendPending());
        config(['services.google_analytics.api_secret' => null]); $this->assertSame(0, $this->send()); Http::assertNothingSent();
        $this->assertNull(Google::browserContext(['client_id' => 'person@example.com']));
        $context = Google::browserContext(['client_id' => '123.456', 'session_id' => '999999999999']);
        $this->assertNull($context['session_id']);
    }

    public function test_rate_limit_retry_preserves_payload_client_and_purchase_transaction_identity()
    {
        $this->fixture(['status' => 'active', 'trial_start' => null, 'trial_end' => null]);
        $this->gateway->invoices = [$this->invoice()]; $this->receipt($this->invoice()); $this->reconcile();
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fakeSequence()->push('', 429)->push('', 204);
        $this->assertSame(0, $this->send()); $first = $this->body();
        DB::table('campaign_visits')->update(['google_context' => Crypt::encryptString(json_encode(Google::browserContext(['client_id' => '999.111'])))]);
        Carbon::setTestNow(now('UTC')->addMinute());
        $this->assertSame(1, $this->send()); $this->assertSame($first, $this->body()); Http::assertSentCount(2);
    }

    public function test_unknown_trial_delivery_outcome_stops_automatic_retries()
    {
        $this->fixture(); $this->reconcile();
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('private-secret-url'));
        $this->assertSame(0, $this->send());
        $this->assertDatabaseHas('google_analytics_conversions', ['state' => 'uncertain', 'last_error' => 'transport_outcome_unknown']);
        Carbon::setTestNow(now('UTC')->addHour()); $this->assertSame(0, $this->send());
        $this->assertStringNotContainsString('private-secret', json_encode(DB::table('google_analytics_conversions')->first()));
    }

    public function test_crashed_trial_dispatch_is_uncertain_but_pre_dispatch_work_can_retry()
    {
        $this->fixture(); $this->reconcile();
        DB::table('google_analytics_conversions')->update(['state' => 'dispatching', 'claimed_until' => now('UTC')->subMinute()]);
        $this->assertSame(0, $this->send()); Http::assertNothingSent();
        $this->assertDatabaseHas('google_analytics_conversions', ['state' => 'uncertain', 'last_error' => 'interrupted_delivery']);
        DB::table('google_analytics_conversions')->update(['state' => 'processing']);
        $this->assertSame(1, $this->send());
    }

    public function test_incomplete_provider_history_retries_without_guessing_a_first_payment()
    {
        $this->fixture(['status' => 'active', 'trial_start' => null, 'trial_end' => null]);
        $this->receipt($this->invoice()); $this->reconcile();
        $gateway = \Mockery::mock(Gateway::class);
        $gateway->shouldReceive('call')->once()->with('us', 'invoices', 'all', \Mockery::on(fn ($p) => $p['subscription'] === 'sub_review'))
            ->andReturn(['data' => [$this->invoice()], 'has_more' => true]);
        $gateway->shouldReceive('call')->once()->with('us', 'invoices', 'all', \Mockery::on(fn ($p) => $p['starting_after'] === 'in_first'))
            ->andReturn(['data' => [], 'has_more' => true]);
        $this->app->instance(Gateway::class, $gateway);
        $this->assertSame(0, $this->send()); Http::assertNothingSent();
        $this->assertDatabaseHas('google_analytics_conversions', ['state' => 'pending', 'last_error' => 'verification_unavailable']);
    }

    public function test_internal_page_shows_only_campaign_conversion_links_without_an_arrivals_counter()
    {
        $this->fixture(); $this->reconcile();
        DB::table('campaign_visits')->insert(['id' => (string) Str::uuid(), 'campaign' => str_repeat('a', 64), 'utm_source' => 'unconverted',
            'created_at' => now(), 'expires_at' => now()->addDays(30)]);
        $pending = (string) Str::uuid();
        DB::table('campaign_visits')->insert(['id' => $pending, 'campaign' => str_repeat('b', 64), 'utm_source' => 'unfinished',
            'user_id' => $this->user->id, 'consented_at' => now(), 'created_at' => now(), 'expires_at' => now()->addDays(30)]);
        DB::table('campaign_subscriptions')->insert(['visit_id' => $pending, 'stripe_subscription_id' => 'sub_unfinished',
            'stripe_customer_id' => 'cus_unfinished', 'billing_account' => 'us', 'plan' => 'monthly', 'subscribed_at' => now()]);
        $this->actingAs(create(Admin::class), 'admin');
        $page = $this->get(route('admin.analytics.campaigns'))->assertOk()->assertSee('Campaign conversions')->assertSee('freepick')
            ->assertSee('Open Google Analytics')->assertDontSee('unconverted')->assertDontSee('unfinished')->assertDontSee('Most arrivals')->assertDontSee('Latest arrival');
        $this->assertSame(1, $page->viewData('campaigns')->total());
        if ($directory = getenv('CAMPAIGN_REPORT_PREVIEW_DIR')) file_put_contents($directory.'/conversions.html', $page->getContent());
    }

    public function test_real_web_checkout_hooks_link_verified_trial_without_browser_conversion_or_live_google_call()
    {
        \Illuminate\Support\Facades\Event::fake();
        \Illuminate\Support\Facades\Notification::fake();
        config(['services.stripe.us_key' => 'pk_test_review', 'services.stripe.us_secret' => 'sk_test_review']);
        $page = $this->get('http://my.localhost/review/campaign-conversion?utm_campaign=launch')->assertOk();
        preg_match('/data-token="([^"]+)"/', $page->getContent(), $match);
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->postJson('http://my.localhost/attribution/consent', ['choice' => 'granted', 'token' => html_entity_decode($match[1]), 'client_id' => '123.456'])->assertOk();
        $visit = DB::table('campaign_visits')->sole();
        $this->withCookies([CampaignAttribution::CONSENT_COOKIE => 'v1:granted', CampaignAttribution::VISIT_COOKIE => $visit->id]);
        $plan = \App\Billing\Plan::forceCreate(['name' => 'monthly', 'price' => 799, 'interval' => 'month', 'trial_period_days' => 7, 'description' => 'Review', 'statement_descriptor' => 'PIANOLIT']);
        $this->gateway->nextSubscription = ['livemode' => true]; // Fake provider only; no real purchase.
        $id = (string) Str::uuid();
        $prepared = $this->postJson(route('webapp.membership.prepare', $plan), ['operation_id' => $id])->assertOk();
        $this->assertDatabaseHas('campaign_checkout_contexts', ['operation_id' => $id, 'visit_id' => $visit->id]);
        $setup = explode('_secret_', $prepared->json('setup_secret'))[0]; $this->gateway->confirm($setup);
        $this->postJson(route('webapp.membership.purchase', $plan), ['operation_id' => $id, 'setup_intent' => $setup])->assertOk();
        $this->postJson(route('webapp.membership.complete'), ['operation_id' => $id])->assertOk();
        $this->postJson(route('webapp.membership.complete'), ['operation_id' => $id])->assertOk();
        $this->assertDatabaseCount('campaign_subscriptions', 1);
        $this->assertDatabaseCount('google_analytics_conversions', 1);
        $this->assertDatabaseHas('google_analytics_conversions', ['kind' => 'trial', 'state' => 'pending']);
        Http::assertNothingSent();
    }
}
