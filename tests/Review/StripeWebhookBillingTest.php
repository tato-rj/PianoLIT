<?php

namespace Tests\Review;

use App\Billing\{Membership, Payment, Plan};
use App\Billing\Sources\Stripe;
use App\Billing\Stripe\{Gateway, MembershipBilling, WebhookProcessor};
use App\Http\Controllers\Webhooks\StripeWebhookController;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Event};
use Illuminate\Support\Str;
use Tests\Support\FakeStripeGateway;

class StripeWebhookBillingTest extends ReviewTestCase
{
    private $user, $gateway, $billing;

    public function setUp(): void
    {
        parent::setUp(); Event::fake();
        config(['attribution.enabled' => false, 'activity.enabled' => false, 'services.stripe.webhook.secret' => 'whsec_us_review',
            'services.stripe.webhook.swiss_secret' => 'whsec_swiss_review', 'services.stripe.us_key' => 'pk_test_review',
            'services.stripe.us_secret' => 'sk_test_review']);
        $this->user = Model::withoutEvents(function () { return create(User::class)->setAppends(['full_name']); });
        $this->actingAs($this->user, 'web');
        $plan = Plan::forceCreate(['name' => 'monthly', 'price' => 799, 'interval' => 'month', 'trial_period_days' => 7, 'description' => 'Review', 'statement_descriptor' => 'PIANOLIT']);
        $this->gateway = new FakeStripeGateway;
        $this->app->instance(Gateway::class, $this->gateway); $this->billing = app(MembershipBilling::class);
        $id = (string) Str::uuid();
        $intent = $this->billing->prepare($this->user, $id, 'purchase', $plan);
        $setup = explode('_secret_', $intent['setup_secret'])[0]; $this->gateway->confirm($setup);
        $this->billing->purchase($this->user, $plan, $id, $setup);
        $this->billing->finish($this->user, $id);
        $this->gateway->calls = [];
    }

    private function payload($type = 'customer.subscription.updated', $id = 'evt_review', array $object = []): array
    {
        return ['id' => $id, 'type' => $type, 'created' => now()->timestamp, 'livemode' => false,
            'data' => ['object' => array_merge(['id' => 'sub_review', 'customer' => 'cus_review'], $object)]];
    }

    private function signed(array $payload, $secret = 'whsec_us_review')
    {
        $body = json_encode($payload); $time = time();
        $request = Request::create('/webhooks/stripe', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => 't='.$time.',v1='.hash_hmac('sha256', $time.'.'.$body, $secret)], $body);
        return (new StripeWebhookController)($request);
    }

    private function source(): Stripe { return $this->user->fresh()->membership->source; }

    public function test_delayed_old_subscription_event_cannot_overwrite_new_subscription()
    {
        $this->signed($this->payload('customer.subscription.deleted', 'evt_old', ['id' => 'sub_old', 'status' => 'canceled', 'ended_at' => now()->timestamp]));
        $this->assertSame('sub_review', $this->source()->subscription_id);
        $this->assertSame('trialing', $this->source()->status);
        $this->assertCount(0, $this->gateway->calls);
        $this->assertNotNull(DB::table('stripe_webhook_events')->value('processed_at'));
    }

    public function test_stale_snapshot_uses_current_provider_state_and_duplicates_do_not_refetch()
    {
        $this->gateway->subscriptions['sub_review']['status'] = 'active';
        $payload = $this->payload('customer.subscription.updated', 'evt_delayed', ['status' => 'past_due', 'card' => ['number' => 'not retained'], 'email' => 'private@example.test']);
        $this->signed($payload); $this->signed($payload);
        $this->assertSame('active', $this->source()->status);
        $this->assertCount(1, $this->gateway->callsTo('subscriptions', 'retrieve'));
        $this->assertDatabaseCount('stripe_webhook_events', 1);
        $this->assertStringNotContainsString('private@example.test', DB::table('stripe_webhook_events')->value('payload'));
        $this->assertStringNotContainsString('not retained', DB::table('stripe_webhook_events')->value('payload'));
    }

    public function test_charge_retries_are_deduplicated_even_when_campaign_attribution_is_disabled()
    {
        $payload = $this->payload('charge.succeeded', 'evt_charge', ['id' => 'ch_review']);
        $this->signed($payload); $this->signed($payload);
        $payload['id'] = 'evt_charge_other'; $this->signed($payload);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseHas('payments', ['charge_id' => 'ch_review', 'user_id' => $this->user->id, 'currency' => 'usd']);
    }

    public function test_local_failure_rolls_back_receipt_and_event_remains_retryable()
    {
        DB::unprepared("CREATE TRIGGER reject_review_payment BEFORE INSERT ON payments BEGIN SELECT RAISE(FAIL, 'review rollback'); END");
        $payload = $this->payload('charge.succeeded', 'evt_retry', ['id' => 'ch_review']);
        try { $this->signed($payload); $this->fail('Expected payment failure'); }
        catch (\Throwable $exception) { $this->assertStringContainsString('review rollback', $exception->getMessage()); }
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseMissing('stripe_payment_receipts', ['stripe_object_id' => 'ch_review']);
        $this->assertNull(DB::table('stripe_webhook_events')->value('processed_at'));
        DB::unprepared('DROP TRIGGER reject_review_payment');
        $this->signed($payload);
        $this->assertDatabaseCount('payments', 1);
        $this->assertSame(2, DB::table('stripe_webhook_events')->value('attempts'));
    }

    public function test_provider_failure_retains_inbox_for_later_replay()
    {
        $this->gateway->failure = ['subscriptions.retrieve', new \RuntimeException('temporary provider failure')];
        try { $this->signed($this->payload()); $this->fail('Expected provider failure'); }
        catch (\RuntimeException $exception) { $this->assertSame('temporary provider failure', $exception->getMessage()); }
        $event = DB::table('stripe_webhook_events')->first();
        $this->assertNull($event->processed_at);
        app(WebhookProcessor::class)->process($event->id);
        $this->assertNotNull(DB::table('stripe_webhook_events')->value('processed_at'));
    }

    public function test_swiss_secret_is_verified_and_cannot_mutate_us_membership()
    {
        $this->signed($this->payload(), 'whsec_swiss_review');
        $this->assertCount(0, $this->gateway->calls);
        $this->assertDatabaseHas('stripe_webhook_events', ['billing_account' => 'swiss']);
    }

    public function test_wrong_and_missing_signature_are_rejected_before_inbox_write()
    {
        try { $this->signed($this->payload(), 'wrong_secret'); $this->fail('Wrong signature accepted'); }
        catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) { $this->assertSame(400, $exception->getStatusCode()); }
        $this->assertDatabaseCount('stripe_webhook_events', 0);
        $this->assertCount(0, $this->gateway->calls);
    }

    public function test_unrelated_shop_source_event_does_not_fail_membership_lookup()
    {
        $this->signed($this->payload('customer.source.deleted', 'evt_shop', ['id' => 'card_review', 'customer' => 'cus_other_shop']));
        $this->assertNotNull(DB::table('stripe_webhook_events')->value('processed_at'));
        $this->assertCount(0, $this->gateway->calls);
    }

    public function test_failed_invoice_refreshes_membership_payment_state_and_recovery_link()
    {
        $this->gateway->subscriptions['sub_review']['status'] = 'past_due';
        $this->gateway->subscriptions['sub_review']['latest_invoice']['status'] = 'open';
        $this->signed($this->payload('invoice.payment_failed', 'evt_failed', ['id' => 'in_review', 'subscription' => 'sub_review']));
        $this->assertSame('past_due', $this->source()->status);
        $this->assertSame('open', $this->source()->payment_status);
        $this->assertSame('https://invoice.stripe.com/i/review', $this->source()->payment_url);
        $this->assertFalse($this->user->fresh()->hasActiveSubscription());
    }

    public function test_reconciliation_audit_is_read_only_and_constructor_has_no_side_effects()
    {
        $before = DB::table('stripe_webhook_events')->count();
        $this->artisan('stripe:reconcile', ['--audit' => true])->assertExitCode(0);
        $this->assertSame($before, DB::table('stripe_webhook_events')->count());
        $this->assertCount(0, $this->gateway->calls);
    }
}
