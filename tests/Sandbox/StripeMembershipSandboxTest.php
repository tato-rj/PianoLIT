<?php

namespace Tests\Sandbox;

use App\Billing\{Plan, Sources\Stripe};
use App\Billing\Stripe\{BillingException, Gateway, MembershipBilling, MembershipManagement, SubscriptionState, WebhookProcessor};
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Event, Mail, Notification};
use Illuminate\Support\Str;
use Tests\Review\ReviewTestCase;

/** Opt-in provider integration. Never included by phpunit-review.xml. */
class StripeMembershipSandboxTest extends ReviewTestCase
{
    private $client, $gateway, $billing, $account, $user, $monthly, $yearly;
    private $clock, $product, $prices = [], $run;

    protected function startSandbox(string $account, int $trial = 7): void
    {
        if (($_SERVER['PIANOLIT_STRIPE_SANDBOX'] ?? '') !== '1') {
            $this->markTestSkipped('Invoke explicitly with phpunit-stripe-sandbox.xml.');
        }
        Event::fake(); Notification::fake();
        $this->run = (string) Str::uuid(); $this->account = $account;
        // Parse credentials as data only. Never bootstrap the normal .env app,
        // import its database/mail configuration or print credential values.
        $vars = \Dotenv\Dotenv::createArrayBacked(base_path())->safeLoad();
        $secret = $vars['STRIPE_'.strtoupper($account).'_SECRET'] ?? ($account === 'us' ? ($vars['STRIPE_SECRET'] ?? '') : '');
        if (!preg_match('/^(sk|rk)_test_/', $secret)) throw new \RuntimeException('Stripe sandbox requires an explicit test secret.');
        config(['services.stripe.'.$account.'_secret' => $secret, 'services.stripe.secret' => null,
            'attribution.enabled' => false, 'activity.enabled' => false]);
        $curl = \Stripe\HttpClient\CurlClient::instance(); $curl->setConnectTimeout(10); $curl->setTimeout(30);
        \Stripe\ApiRequestor::setHttpClient($curl);
        $this->client = new \Stripe\StripeClient(['api_key' => $secret, 'stripe_version' => '2025-03-31.basil', 'max_network_retries' => 1]);
        $balance = $this->api(function () { return $this->client->balance->retrieve(); });
        if ($balance->livemode !== false) throw new \RuntimeException('Stripe did not confirm test mode. No fixtures created.');
        $this->clock = $this->api(function () {
            return $this->client->testHelpers->testClocks->create(['frozen_time' => time(), 'name' => 'PianoLIT sandbox '.$this->run]);
        });
        $this->assertFalse($this->clock->livemode);
        $this->product = $this->api(function () {
            return $this->client->products->create(['name' => 'PianoLIT sandbox '.$this->run, 'metadata' => ['pianolit_sandbox_run' => $this->run]]);
        });
        foreach (['monthly' => [799, 'month'], 'yearly' => [8999, 'year']] as $name => $definition) {
            $price = $this->api(function () use ($definition) {
                return $this->client->prices->create(['product' => $this->product->id, 'currency' => 'usd', 'unit_amount' => $definition[0],
                    'recurring' => ['interval' => $definition[1]], 'metadata' => ['pianolit_sandbox_run' => $this->run]]);
            });
            $this->assertFalse($price->livemode); $this->prices[] = $price->id;
            config(['memberships.stripe_prices.'.$account.'.'.$name => $price->id]);
            $plan = Plan::forceCreate(['name' => $name, 'price' => $definition[0], 'interval' => $definition[1],
                'trial_period_days' => $trial, 'description' => 'Sandbox fixture', 'statement_descriptor' => 'PIANOLIT']);
            if ($name === 'monthly') $this->monthly = $plan; else $this->yearly = $plan;
        }
        $this->user = Model::withoutEvents(function () {
            return create(User::class, ['email' => 'stripe-sandbox-'.$this->run.'@example.test', 'first_name' => 'Stripe', 'last_name' => 'Sandbox', 'super_user' => false]);
        });
        // Select a legacy-account path without changing the public checkout's
        // default account. The disposable profile contains no existing customer.
        DB::table('stripe_billing_profiles')->insert(['id' => (string) Str::uuid(), 'user_id' => $this->user->id,
            'billing_account' => $account, 'customer_id' => null, 'created_at' => now(), 'updated_at' => now()]);
        $this->gateway = new ClockedSandboxGateway($account, $this->clock->id);
        $this->app->instance(Gateway::class, $this->gateway);
        $this->billing = app(MembershipBilling::class);
        $this->progress('isolated test-mode fixtures ready');
    }

    public function tearDown(): void
    {
        $cleanup = [];
        try {
            if ($this->clock) {
                try { $this->api(function () { return $this->client->testHelpers->testClocks->delete($this->clock->id); }); }
                catch (\Throwable $e) { $cleanup[] = 'test clock '.$this->clock->id; }
            }
            foreach ($this->prices as $price) {
                try { $this->api(function () use ($price) { return $this->client->prices->update($price, ['active' => false]); }); }
                catch (\Throwable $e) { $cleanup[] = 'price '.$price; }
            }
            if ($this->product) {
                try { $this->api(function () { return $this->client->products->update($this->product->id, ['active' => false]); }); }
                catch (\Throwable $e) { $cleanup[] = 'product '.$this->product->id; }
            }
        } finally {
            \Stripe\ApiRequestor::setHttpClient(null);
            parent::tearDown();
        }
        if ($cleanup) throw new \RuntimeException('Sandbox fixture cleanup needs review: '.implode(', ', $cleanup));
    }

    private function api(callable $request)
    {
        try { return $request(); }
        catch (\Stripe\Exception\ApiErrorException $e) {
            throw new \RuntimeException('Stripe sandbox API failure: '.json_encode(['type' => get_class($e), 'code' => $e->getStripeCode(),
                'param' => $e->getError()->param ?? null, 'http_status' => $e->getHttpStatus(), 'request_id' => $e->getRequestId()]));
        }
    }

    private function progress(string $stage): void { fwrite(STDERR, '[sandbox '.$this->account.'] '.$stage.PHP_EOL); }
    private function source(): Stripe { return $this->user->fresh()->membership->source; }

    private function confirmedSetup(string $kind = 'purchase', string $method = 'pm_card_visa'): array
    {
        $operation = (string) Str::uuid();
        $prepared = $this->billing->prepare($this->user, $operation, $kind, $kind === 'purchase' ? $this->monthly : null);
        $id = explode('_secret_', $prepared['setup_secret'])[0];
        $intent = $this->api(function () use ($id, $method) { return $this->client->setupIntents->confirm($id, ['payment_method' => $method]); });
        $this->assertFalse($intent->livemode);
        return [$operation, $id, $intent];
    }

    /** @dataProvider accounts */
    public function test_trial_card_and_membership_management_against_real_stripe_test_mode(string $account)
    {
        $this->startSandbox($account);
        [$operation, $setup, $intent] = $this->confirmedSetup();
        $this->assertSame('succeeded', $intent->status);
        $purchase = $this->billing->purchase($this->user, $this->monthly, $operation, $setup);
        $this->progress('checkout response '.json_encode(['status' => $purchase['status'], 'complete' => $purchase['complete'],
            'needs_subscription_setup' => !empty($purchase['subscription_setup_secret']), 'needs_payment' => !empty($purchase['payment_secret'])]));
        if (!empty($purchase['subscription_setup_secret'])) {
            // Simulate the second confirmation performed by confirmPayment()
            // in membership-billing.js; a trial can have a pending setup intent.
            $pending = explode('_secret_', $purchase['subscription_setup_secret'])[0];
            $confirmed = $this->api(function () use ($pending) { return $this->client->setupIntents->confirm($pending); });
            $this->assertSame('succeeded', $confirmed->status);
            $purchase = $this->billing->purchase($this->user, $this->monthly, $operation, $setup);
        }
        $this->assertTrue($purchase['complete']);
        $this->assertSame('trialing', $purchase['status']);
        $this->assertTrue($this->billing->finish($this->user, $operation)['first_completion']);
        $this->assertFalse($this->billing->finish($this->user, $operation)['first_completion']);
        $before = $this->source(); $period = $before->renews_at->timestamp;
        $this->assertSame($account, $before->billing_account);
        $this->assertTrue($this->user->fresh()->hasActiveSubscription());
        $oldMethod = $before->payment_method_id;
        $this->progress('trial and duplicate finish verified');

        [$cardOperation, $cardSetup] = $this->confirmedSetup('card', 'pm_card_mastercard');
        $this->billing->updateCard($this->user, $cardOperation, $cardSetup);
        $this->assertSame('mastercard', $this->source()->card_brand);
        $this->assertNotSame($oldMethod, $this->source()->payment_method_id);
        $oldCard = $this->api(function () use ($oldMethod) { return $this->client->paymentMethods->retrieve($oldMethod); });
        $this->assertSame($before->stripe_id, $oldCard->customer);
        $management = app(MembershipManagement::class);
        $management->change($this->user, (string) Str::uuid(), 'plan', $this->yearly);
        $this->assertSame('monthly', $this->source()->plan);
        $this->assertSame('yearly', $this->source()->pending_plan);
        $this->assertSame($period, $this->source()->plan_changes_at->timestamp);
        $this->progress('safe card replacement and renewal-time plan scheduling verified');

        $management->change($this->user, (string) Str::uuid(), 'cancel');
        $this->assertSame($period, $this->source()->membership_ends_at->timestamp);
        $this->assertNull($this->source()->pending_plan);
        $this->assertTrue($this->user->fresh()->hasActiveSubscription());
        $management->change($this->user, (string) Str::uuid(), 'resume');
        $this->assertNull($this->source()->membership_ends_at);
        $this->assertSame($period, $this->source()->renews_at->timestamp);
        $management->change($this->user, (string) Str::uuid(), 'pause');
        $this->assertNotNull($this->source()->paused_at);
        $management->change($this->user, (string) Str::uuid(), 'unpause');
        $this->assertNull($this->source()->paused_at);
        $this->assertSame($period, $this->source()->renews_at->timestamp);
        $invoices = $management->invoices($this->user);
        $this->assertNotEmpty($invoices['invoices']);
        $this->assertNotNull($invoices['upcoming']);
        $subscriptions = $this->billing->subscriptions($account, $before->stripe_id);
        $this->assertCount(1, $subscriptions);
        $this->assertSame($before->subscription_id, $subscriptions[0]['id']);
        Mail::assertNothingSent(); Mail::assertNothingQueued(); Notification::assertNothingSent();
        $this->progress('cancel/resume, pause/unpause and invoices verified');

        $management->change($this->user, (string) Str::uuid(), 'plan', $this->yearly);
        $this->advanceClock($period + 5);
        $this->advanceClock($period + 7200);
        $renewed = $this->billing->subscription($this->user);
        $this->assertSame('active', $renewed['status']);
        $this->assertSame('yearly', $this->source()->plan);
        $this->assertNull($this->source()->pending_plan);
        $this->assertSame(8999, $renewed['latest_invoice']['amount_paid']);
        $this->assertGreaterThan($period + 300 * 86400, SubscriptionState::periodEnd($renewed));
        $this->progress('test-clock trial conversion and annual plan boundary verified');
    }

    /** @dataProvider accounts */
    public function test_paid_checkout_decline_recovery_charge_replay_and_refund(string $account)
    {
        $this->startSandbox($account, 0);
        [$operation, $setup, $intent] = $this->confirmedSetup('purchase', 'pm_card_chargeCustomerFail');
        $this->assertSame('succeeded', $intent->status);
        $purchase = $this->billing->purchase($this->user, $this->monthly, $operation, $setup);
        $this->assertFalse($purchase['complete']);
        $this->assertFalse($this->user->fresh()->hasActiveSubscription());
        $payment = explode('_secret_', $purchase['payment_secret'])[0];
        try {
            $this->api(function () use ($payment) { return $this->client->paymentIntents->confirm($payment); });
            $this->fail('The failing test card must be declined.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('"code":"card_declined"', $e->getMessage());
        }
        try {
            $this->billing->finish($this->user, $operation);
            $this->fail('A declined checkout must not finish.');
        } catch (BillingException $e) {
            $this->assertFalse($this->user->fresh()->hasActiveSubscription());
        }
        $retry = $this->billing->prepare($this->user, $operation, 'purchase', $this->monthly);
        $this->assertSame($payment, explode('_secret_', $retry['payment_secret'])[0]);
        // This is the replacement-card confirmation made by the browser on retry.
        $paid = $this->api(function () use ($payment) {
            return $this->client->paymentIntents->confirm($payment, ['payment_method' => 'pm_card_visa']);
        });
        $this->assertSame('succeeded', $paid->status);
        $this->assertTrue($this->billing->finish($this->user, $operation)['first_completion']);
        $this->assertFalse($this->billing->finish($this->user, $operation)['first_completion']);
        $this->assertSame('active', $this->source()->status);
        $this->assertTrue($this->user->fresh()->hasActiveSubscription());
        $this->assertCount(1, $this->billing->subscriptions($account, $this->source()->stripe_id));
        $this->progress('paid checkout decline/replacement recovery and idempotent finish verified');

        $event = $this->providerEvent('charge.succeeded', $paid->latest_charge);
        $processor = app(WebhookProcessor::class);
        $processor->receive($event, $account); $processor->receive($event, $account);
        $this->assertSame(1, DB::table('payments')->where('charge_id', $paid->latest_charge)->count());
        $this->assertSame($this->user->id, DB::table('payments')->where('charge_id', $paid->latest_charge)->value('user_id'));
        $this->assertSame(799, DB::table('payments')->where('charge_id', $paid->latest_charge)->value('amount'));
        $this->api(function () use ($paid) { return $this->client->refunds->create(['charge' => $paid->latest_charge, 'amount' => 100]); });
        $refunded = $this->providerEvent('charge.refunded', $paid->latest_charge);
        $processor->receive($refunded, $account); $processor->receive($refunded, $account);
        $this->assertSame(1, DB::table('payments')->where('charge_id', $paid->latest_charge)->count());
        $this->assertSame(100, DB::table('payments')->where('charge_id', $paid->latest_charge)->value('refund'));
        $this->assertSame(0, DB::table('stripe_webhook_events')->whereNull('processed_at')->count());
        Mail::assertNothingSent(); Mail::assertNothingQueued(); Notification::assertNothingSent();
        $this->progress('actual Stripe charge/refund events and duplicate inbox replay verified');
    }

    /** @dataProvider accounts */
    public function test_setup_authentication_and_decline_cannot_authorize_purchase(string $account)
    {
        $this->startSandbox($account);
        $operation = (string) Str::uuid();
        $prepared = $this->billing->prepare($this->user, $operation, 'purchase', $this->monthly);
        $setup = explode('_secret_', $prepared['setup_secret'])[0];
        $intent = $this->api(function () use ($setup) {
            return $this->client->setupIntents->confirm($setup, ['payment_method' => 'pm_card_authenticationRequired', 'use_stripe_sdk' => true]);
        });
        $this->assertSame('requires_action', $intent->status);
        try {
            $this->billing->purchase($this->user, $this->monthly, $operation, $setup);
            $this->fail('Unfinished bank authentication must not authorize a purchase.');
        } catch (BillingException $e) {
            $this->assertNull($this->billing->source($this->user, false));
        }
        try {
            $this->api(function () use ($setup) {
                return $this->client->setupIntents->confirm($setup, ['payment_method' => 'pm_card_visa_chargeDeclined']);
            });
            $this->fail('The declined test setup must fail.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('"code":"card_declined"', $e->getMessage());
        }
        $this->assertCount(0, $this->billing->subscriptions($account, $intent->customer));
        $this->assertFalse($this->user->fresh()->hasActiveSubscription());
        $this->progress('real 3DS requires-action and card-decline gates verified; interactive challenge still requires browser testing');
    }

    private function advanceClock(int $timestamp): void
    {
        $clock = $this->api(function () use ($timestamp) {
            return $this->client->testHelpers->testClocks->advance($this->clock->id, ['frozen_time' => $timestamp]);
        });
        $deadline = microtime(true) + 45;
        while ($clock->status === 'advancing' && microtime(true) < $deadline) {
            usleep(1000000);
            $clock = $this->api(function () { return $this->client->testHelpers->testClocks->retrieve($this->clock->id); });
        }
        $this->assertSame('ready', $clock->status, 'Test clock did not become ready within the bounded wait.');
    }

    private function providerEvent(string $type, string $object): array
    {
        $deadline = microtime(true) + 20;
        do {
            $events = $this->api(function () use ($type) { return $this->client->events->all(['type' => $type, 'limit' => 100]); });
            foreach ($events->data as $event) {
                if ($event->data->object->id === $object) {
                    $this->assertFalse($event->livemode);
                    return $event->toArray();
                }
            }
            usleep(1000000);
        } while (microtime(true) < $deadline);
        throw new \RuntimeException('Timed out waiting for this fixture\'s Stripe '.$type.' event.');
    }

    public static function accounts(): array { return [['us'], ['swiss']]; }
}

/** Test-only clock injection, account guard and redacted provider errors. */
class ClockedSandboxGateway extends Gateway
{
    private $account, $clock;

    public function __construct(string $account, string $clock) { $this->account = $account; $this->clock = $clock; }

    public function call(string $account, string $resource, string $method, array $parameters = [], ?string $id = null, ?string $key = null): array
    {
        if ($account !== $this->account || !preg_match('/^(sk|rk)_test_/', config('services.stripe.'.$account.'_secret') ?? '')) {
            throw new \RuntimeException('Sandbox refused an unexpected account or non-test credential.');
        }
        if ($resource === 'customers' && $method === 'create') $parameters['test_clock'] = $this->clock;
        try {
            $result = parent::call($account, $resource, $method, $parameters, $id, $key);
            if (array_key_exists('livemode', $result) && $result['livemode'] !== false) throw new \RuntimeException('Unexpected provider mode.');
            return $result;
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new \RuntimeException('Stripe sandbox '.$resource.'.'.$method.' failure: '.json_encode(['type' => get_class($e),
                'code' => $e->getStripeCode(), 'param' => $e->getError()->param ?? null,
                'http_status' => $e->getHttpStatus(), 'request_id' => $e->getRequestId()]));
        }
    }
}
