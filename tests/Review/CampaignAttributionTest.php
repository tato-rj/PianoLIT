<?php

namespace Tests\Review;

use App\{Piece, Tag, User};
use App\Billing\{Payment, Plan};
use App\Billing\Factories\StripeFactory;
use App\Billing\Webhooks\StripeWebhooks;
use App\Services\{CampaignAttribution, CampaignAttributionReport, StripePaymentReceipts};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Cookie, DB, Event, Notification, Redis};

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
        return (new \DOMXPath($dom))->query('//*[@data-campaign-measurement]')->item(0)->getAttribute('data-token');
    }

    private function grant()
    {
        $token = $this->token($this->landing());
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
        $plan = Plan::forceCreate(['name' => 'monthly', 'price' => 999, 'interval' => 'month', 'trial_period_days' => 7, 'description' => 'Review plan', 'statement_descriptor' => 'PIANOLIT']);
        $factory = \Mockery::mock(StripeFactory::class);
        $factory->purchasedSubscription = $subscription;
        $factory->shouldReceive('customer')->once()->andReturnSelf();
        $factory->shouldReceive('withCoupon')->once()->with('')->andReturnSelf();
        if ($fails) $factory->shouldReceive('subscribe')->once()->andThrow(new \RuntimeException('Review payment failure'));
        else $factory->shouldReceive('subscribe')->once()->andReturn($this->customer($subscription));
        $this->app->instance(StripeFactory::class, $factory);
        return $this->post(route('webapp.membership.purchase', $plan), ['stripeToken' => 'review-token']);
    }

    private function report()
    {
        return (new CampaignAttributionReport)->rows(now()->subDay()->toDateString())[0];
    }

    public function test_guest_arrival_requires_opt_in_and_strict_campaign_and_piece_match()
    {
        $page = $this->landing()->assertDontSee('GTM-KJW7XGP')->assertSee('Allow measurement');
        $this->assertNotEmpty($this->token($page));
        if ($path = getenv('CAMPAIGN_ATTRIBUTION_PREVIEW')) {
            preg_match('/<aside class="container my-3"[\s\S]*?<\/aside>/', $page->getContent(), $match);
            file_put_contents($path, '<!doctype html><html><head><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="stylesheet" href="/css/app.css"></head><body><main class="container py-4"><h2>Notturno</h2><p>Optional campaign measurement preview</p></main>'.($match[0] ?? '').'<script src="/js/views/campaign-attribution.js"></script></body></html>');
        }
        $this->assertDatabaseCount('campaign_visits', 0);
        foreach (['utm_source' => 'someone@example.com', 'utm_campaign' => 'other', 'utm_content' => ['description']] as $field => $value) {
            $this->assertSame('', $this->token($this->landing([$field => $value])));
        }
        $this->assertNull($this->attribution->campaignFromToken('forged'));
        $token = $this->token($page);
        $this->travel(11)->minutes();
        $this->assertNull($this->attribution->campaignFromToken($token));
        $this->withExceptionHandling()->post(route('webapp.attribution.consent'), ['choice' => 'granted', 'token' => $token])->assertStatus(422);
        $this->assertDatabaseCount('campaign_visits', 0);
    }

    public function test_refusal_refresh_and_direct_navigation_do_not_add_arrivals()
    {
        $this->post(route('webapp.attribution.consent'), ['choice' => 'denied'])->assertOk();
        $this->assertDatabaseCount('campaign_visits', 0);
        [$visit, $token, $response] = $this->grant();
        // Duplicate acknowledgement, including a retry before the cookie arrives.
        $this->defaultCookies = [];
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
        $this->checkout($this->subscription())->assertRedirect(route('webapp.membership.success'));
        $this->assertDatabaseHas('campaign_subscriptions', ['stripe_subscription_id' => 'sub_first', 'stripe_customer_id' => 'cus_first', 'billing_account' => 'us']);
        $this->assertSame(1, $this->report()['trials']);
        $this->assertSame(0, $this->report()['first_paid_subscriptions']);
        $this->get(route('webapp.membership.success'))->assertOk();
        $this->get(route('webapp.membership.success'))->assertRedirect();
        $this->assertSame(1, $this->report()['trials']);
    }

    public function test_failed_checkout_does_not_record_a_trial_or_subscription()
    {
        $this->grant();
        $this->actingAs($this->user, 'web');
        $this->checkout($this->subscription(), true)->assertRedirect();
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
        $this->checkout($this->subscription())->assertRedirect();
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
        $this->checkout($this->subscription('sub_first', 'active'))->assertRedirect();
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
        $this->checkout($this->subscription())->assertRedirect();
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
        $this->checkout($this->subscription())->assertRedirect();
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
        $this->checkout($this->subscription())->assertRedirect();
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
