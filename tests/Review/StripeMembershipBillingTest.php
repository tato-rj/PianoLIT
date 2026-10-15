<?php

namespace Tests\Review;

use App\Billing\{Customer, Membership, Payment, Plan};
use App\Billing\Sources\{Apple, Stripe};
use App\Billing\Stripe\{Accounts, BillingException, Gateway, MembershipBilling, MembershipManagement, Operations, WebhookProcessor};
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Event, Notification};
use Illuminate\Support\Str;
use Tests\Support\FakeStripeGateway;

class StripeMembershipBillingTest extends ReviewTestCase
{
    private $user, $plan, $gateway, $billing;

    public function setUp(): void
    {
        parent::setUp();
        Event::fake(); Notification::fake();
        config(['attribution.enabled' => false, 'activity.enabled' => false,
            'services.stripe.us_key' => 'pk_test_us_review', 'services.stripe.us_secret' => 'sk_test_us_review',
            'services.stripe.swiss_key' => 'pk_test_swiss_review', 'services.stripe.swiss_secret' => 'sk_test_swiss_review']);
        $this->withoutMiddleware([\App\Http\Middleware\Logs\RecordWebAppLog::class, \App\Http\Middleware\UpdateLocation::class]);
        $this->user = Model::withoutEvents(function () { return create(User::class)->setAppends(['full_name']); });
        $this->actingAs($this->user, 'web');
        $this->plan = Plan::forceCreate(['name' => 'monthly', 'price' => 799, 'interval' => 'month', 'trial_period_days' => 7, 'description' => 'Review plan', 'statement_descriptor' => 'PIANOLIT']);
        Plan::forceCreate(['name' => 'yearly', 'price' => 8999, 'interval' => 'year', 'trial_period_days' => 7, 'description' => 'Review plan', 'statement_descriptor' => 'PIANOLIT']);
        $this->gateway = new FakeStripeGateway;
        $this->app->instance(Gateway::class, $this->gateway);
        $this->billing = app(MembershipBilling::class);
    }

    private function prepare($kind = 'purchase', $id = null, $coupon = null): array
    {
        $id = $id ?: (string) Str::uuid();
        $response = $this->billing->prepare($this->user, $id, $kind, $kind === 'purchase' ? $this->plan : null, $coupon);
        if (isset($response['setup_secret'])) $this->gateway->confirm(explode('_secret_', $response['setup_secret'])[0]);
        return [$response['operation_id'], isset($response['setup_secret']) ? explode('_secret_', $response['setup_secret'])[0] : null];
    }

    private function purchase(array $overrides = [], $finish = true): array
    {
        $this->gateway->nextSubscription = $overrides ?: null;
        [$id, $setup] = $this->prepare();
        $result = $this->billing->purchase($this->user, $this->plan, $id, $setup);
        if ($finish) $this->billing->finish($this->user, $id);
        return [$id, $setup, $result];
    }

    private function source(): Stripe { return $this->user->fresh()->membership->source; }

    public function test_trial_requires_verified_setup_and_uses_modern_subscription_identity()
    {
        [$id, $setup, $result] = $this->purchase();
        $this->assertTrue($result['complete']);
        $this->assertSame('sub_review', $this->source()->subscription_id);
        $this->assertSame('us', $this->source()->billing_account);
        $this->assertTrue($this->user->fresh()->hasActiveSubscription());
        $this->assertSame('4444', $this->source()->card_last_four);
        $this->assertDatabaseHas('stripe_billing_operations', ['id' => $id, 'state' => 'complete', 'active_key' => null]);
        $call = $this->gateway->callsTo('subscriptions', 'create')[0];
        $this->assertSame('default_incomplete', $call['parameters']['payment_behavior']);
        $this->assertSame('pm_review', $call['parameters']['default_payment_method']);
        $this->assertArrayNotHasKey('source', $call['parameters']);
    }

    public function test_card_setup_must_belong_to_current_operation_and_customer()
    {
        [$id, $setup] = $this->prepare();
        $this->gateway->intents[$setup]['customer'] = 'cus_someone_else';
        $this->expectException(BillingException::class);
        try { $this->billing->purchase($this->user, $this->plan, $id, $setup); }
        finally { $this->assertCount(0, $this->gateway->callsTo('subscriptions', 'create')); }
    }

    public function test_unconfirmed_setup_does_not_create_subscription()
    {
        [$id, $setup] = $this->prepare();
        $this->gateway->intents[$setup]['status'] = 'requires_action';
        $this->expectException(BillingException::class);
        $this->billing->purchase($this->user, $this->plan, $id, $setup);
    }

    public function test_incomplete_payment_never_finishes_or_grants_access()
    {
        [$id, $setup, $result] = $this->purchase(['status' => 'incomplete'], false);
        $this->assertFalse($result['complete']);
        $this->assertSame('pi_review_secret_test', $result['payment_secret']);
        $this->assertFalse($this->user->fresh()->hasActiveSubscription());
        $this->expectException(BillingException::class);
        $this->billing->finish($this->user, $id);
    }

    public function test_payment_confirmation_refreshes_provider_state_and_completion_is_once()
    {
        [$id] = $this->purchase(['status' => 'incomplete'], false);
        $this->gateway->subscriptions['sub_review']['status'] = 'active';
        $this->assertTrue($this->billing->finish($this->user, $id)['first_completion']);
        $this->assertFalse($this->billing->finish($this->user, $id)['first_completion']);
        $this->assertTrue($this->user->fresh()->hasActiveSubscription());
    }

    public function test_retries_reuse_customer_setup_and_subscription()
    {
        [$id, $setup] = $this->purchase();
        $this->billing->prepare($this->user, $id, 'purchase', $this->plan);
        $this->billing->purchase($this->user, $this->plan, $id, $setup);
        $this->assertCount(1, $this->gateway->callsTo('customers', 'create'));
        $this->assertCount(1, $this->gateway->callsTo('setupIntents', 'create'));
        $this->assertCount(1, $this->gateway->callsTo('subscriptions', 'create'));
        $this->assertSame(1, $this->user->membership()->count());
    }

    public function test_two_checkout_tabs_reuse_one_unfinished_operation()
    {
        [$first] = $this->prepare();
        [$second] = $this->prepare();
        $this->assertSame($first, $second);
        $this->assertDatabaseCount('stripe_billing_operations', 1);
    }

    public function test_coupon_can_be_corrected_before_subscription_creation()
    {
        $id = (string) Str::uuid();
        $this->gateway->coupons['EXPIRED'] = ['id' => 'EXPIRED', 'valid' => false];
        try { $this->billing->prepare($this->user, $id, 'purchase', $this->plan, 'EXPIRED'); }
        catch (BillingException $exception) { $this->assertStringContainsString('coupon', $exception->getMessage()); }
        [$id, $setup] = $this->prepare('purchase', $id, 'HALF');
        $this->billing->purchase($this->user, $this->plan, $id, $setup);
        $this->assertSame([['coupon' => 'HALF']], $this->gateway->callsTo('subscriptions', 'create')[0]['parameters']['discounts']);
    }

    public function test_expired_apple_account_is_blocked_before_any_stripe_request()
    {
        $apple = Model::withoutEvents(function () { return create(Apple::class, ['renews_at' => now()->subMonth()]); });
        Membership::create(['user_id' => $this->user->id, 'source_type' => Apple::class, 'source_id' => $apple->id]);
        try { $this->prepare(); $this->fail('App Store account accepted a second provider'); }
        catch (BillingException $exception) { $this->assertStringContainsString('App Store', $exception->getMessage()); }
        $this->assertCount(0, $this->gateway->calls);
        $this->assertSame(1, $this->user->membership()->count());
    }

    public function test_returning_subscription_uses_paid_period_and_clears_previous_cancellation()
    {
        $this->purchase();
        $source = $this->source();
        $source->update(['status' => 'canceled', 'ended_at' => now()->subDay(), 'membership_ends_at' => now()->subDay(), 'paused_at' => now()->subWeek()]);
        $this->gateway->subscriptions['sub_review']['status'] = 'canceled';
        $this->gateway->nextSubscription = ['id' => 'sub_returning', 'status' => 'active'];
        [$id, $setup] = $this->prepare('purchase', null, 'HALF');
        $this->billing->purchase($this->user, $this->plan, $id, $setup);
        $this->billing->finish($this->user, $id);
        $this->assertNull($this->source()->ended_at);
        $this->assertNull($this->source()->paused_at);
        $this->assertNull($this->source()->membership_ends_at);
        $this->assertTrue($this->source()->renews_at->isFuture());
        $this->assertTrue($this->user->fresh()->hasActiveSubscription());
        $calls = $this->gateway->callsTo('subscriptions', 'create');
        $this->assertArrayNotHasKey('trial_period_days', $calls[1]['parameters']);
        $this->assertSame([['coupon' => 'HALF']], $calls[1]['parameters']['discounts']);
    }

    public function test_card_update_never_deletes_old_card_or_changes_separate_shop_customer()
    {
        $this->purchase();
        $shop = Customer::create(['user_id' => $this->user->id, 'stripe_id' => 'cus_shop_other', 'card_brand' => 'mastercard', 'card_last_four' => '1111']);
        [$id, $setup] = $this->prepare('card');
        $this->billing->updateCard($this->user, $id, $setup);
        $this->billing->updateCard($this->user, $id, $setup);
        $this->assertSame('1111', $shop->fresh()->card_last_four);
        $this->assertSame('4444', $this->source()->card_last_four);
        $this->assertCount(1, $this->gateway->callsTo('subscriptions', 'update'));
        $this->assertCount(0, $this->gateway->callsTo('paymentMethods', 'detach'));
    }

    public function test_failed_card_update_retains_existing_local_card()
    {
        $this->purchase(); $this->source()->update(['card_last_four' => '4242']);
        [$id, $setup] = $this->prepare('card');
        $this->gateway->failure = ['subscriptions.update', new \RuntimeException('provider unavailable')];
        try { $this->billing->updateCard($this->user, $id, $setup); $this->fail('Expected provider failure'); }
        catch (\RuntimeException $exception) { $this->assertSame('4242', $this->source()->card_last_four); }
        $this->billing->updateCard($this->user, $id, $setup);
        $this->assertSame('4444', $this->source()->card_last_four);
    }

    public function test_plan_change_is_scheduled_without_changing_current_plan_or_charging()
    {
        $this->purchase();
        app(MembershipManagement::class)->change($this->user, (string) Str::uuid(), 'plan', Plan::byName('yearly'));
        $this->assertSame('monthly', $this->source()->plan);
        $this->assertSame('yearly', $this->source()->pending_plan);
        $this->assertTrue($this->source()->plan_changes_at->isFuture());
        $phases = $this->gateway->callsTo('subscriptionSchedules', 'update')[0]['parameters']['phases'];
        $this->assertSame('monthly', $phases[0]['items'][0]['price']);
        $this->assertSame('yearly', $phases[1]['items'][0]['price']);
        $this->assertSame($phases[0]['end_date'], $phases[1]['start_date']);
        $this->assertSame([['discount' => 'di_existing']], $phases[1]['discounts']);
        $this->assertCount(0, $this->gateway->callsTo('subscriptions', 'update'));
    }

    public function test_cancel_updates_full_state_preserves_access_and_releases_our_schedule()
    {
        $this->purchase();
        $management = app(MembershipManagement::class);
        $management->change($this->user, (string) Str::uuid(), 'plan', Plan::byName('yearly'));
        $id = (string) Str::uuid();
        $management->change($this->user, $id, 'cancel');
        $management->change($this->user, $id, 'cancel');
        $this->assertNull($this->source()->renews_at);
        $this->assertNull($this->source()->pending_plan);
        $this->assertTrue($this->source()->membership_ends_at->isFuture());
        $this->assertTrue($this->user->fresh()->hasActiveSubscription());
        $this->assertCount(1, $this->gateway->callsTo('subscriptionSchedules', 'release'));
        $management->change($this->user, (string) Str::uuid(), 'resume');
        $this->assertNull($this->source()->membership_ends_at);
        $this->assertTrue($this->source()->renews_at->isFuture());
    }

    public function test_canceled_subscription_cannot_be_reactivated_by_plan_post()
    {
        $this->purchase();
        $management = app(MembershipManagement::class);
        $management->change($this->user, (string) Str::uuid(), 'cancel');
        $this->expectException(BillingException::class);
        $management->change($this->user, (string) Str::uuid(), 'plan', Plan::byName('yearly'));
    }

    public function test_pause_is_explicit_and_repeated_requests_do_not_unpause()
    {
        $this->purchase();
        $management = app(MembershipManagement::class); $id = (string) Str::uuid();
        $management->change($this->user, $id, 'pause');
        $management->change($this->user, $id, 'pause');
        $this->assertTrue($this->source()->isPaused());
        $this->assertFalse($this->user->fresh()->hasActiveSubscription());
        $this->assertCount(1, $this->gateway->callsTo('subscriptions', 'update'));
        $management->change($this->user, (string) Str::uuid(), 'unpause');
        $this->assertFalse($this->source()->isPaused());
    }

    public function test_open_invoice_blocks_pause_instead_of_promising_no_more_charges()
    {
        $this->purchase();
        $this->gateway->invoices = [['id' => 'in_open', 'status' => 'open']];
        $this->expectException(BillingException::class);
        app(MembershipManagement::class)->change($this->user, (string) Str::uuid(), 'pause');
    }

    public function test_foreign_operation_is_rejected_without_stripe_calls()
    {
        [$id] = $this->prepare(); $count = count($this->gateway->calls);
        $other = Model::withoutEvents(function () { return create(User::class); });
        try { $this->billing->prepare($other, $id, 'purchase', $this->plan); $this->fail('Foreign operation accepted'); }
        catch (BillingException $exception) { $this->assertCount($count, $this->gateway->calls); }
    }

    public function test_wrong_remote_price_prevents_purchase()
    {
        $this->gateway->prices['monthly'] = ['id' => 'monthly', 'active' => true, 'currency' => 'usd', 'unit_amount' => 1, 'recurring' => ['interval' => 'month']];
        $this->expectException(BillingException::class);
        try { $this->prepare(); } finally { $this->assertCount(0, $this->gateway->callsTo('subscriptions', 'create')); }
    }

    public function test_legacy_subscription_lookup_refuses_ambiguous_remote_subscriptions()
    {
        $this->purchase(); $this->source()->update(['subscription_id' => null]);
        $this->gateway->subscriptions['sub_other'] = array_replace($this->gateway->subscriptions['sub_review'], ['id' => 'sub_other']);
        $this->expectException(BillingException::class);
        $this->billing->subscription($this->user);
    }

    public function test_guest_and_malformed_browser_requests_are_rejected_before_billing()
    {
        $this->withExceptionHandling();
        $this->postJson(route('webapp.membership.prepare', $this->plan), ['operation_id' => 'bad'])->assertStatus(422);
        $this->postJson(route('webapp.membership.purchase', $this->plan), ['stripeToken' => 'legacy-token'])->assertStatus(422);
        $this->postJson(route('webapp.membership.update.plan'), ['operation_id' => (string) Str::uuid(), 'plan' => 'unlisted'])->assertStatus(422);
        auth('web')->logout();
        $this->postJson(route('webapp.membership.prepare.card'), ['operation_id' => (string) Str::uuid()])->assertUnauthorized();
        $this->assertCount(0, $this->gateway->calls);
    }

    public function test_remote_subscription_is_recovered_after_local_transaction_rolls_back()
    {
        [$id, $setup] = $this->prepare();
        DB::unprepared("CREATE TRIGGER reject_review_membership BEFORE INSERT ON memberships BEGIN SELECT RAISE(FAIL, 'review membership rollback'); END");
        try { $this->billing->purchase($this->user, $this->plan, $id, $setup); $this->fail('Expected local failure'); }
        catch (\Throwable $exception) { $this->assertStringContainsString('review membership rollback', $exception->getMessage()); }
        $this->assertDatabaseCount('stripe_memberships', 0);
        $this->assertDatabaseCount('memberships', 0);
        $this->assertNull(DB::table('stripe_billing_operations')->value('subscription_id'));
        $this->assertCount(1, $this->gateway->subscriptions);
        DB::unprepared('DROP TRIGGER reject_review_membership');
        $this->billing->purchase($this->user, $this->plan, $id, $setup);
        $this->billing->finish($this->user, $id);
        $this->assertCount(1, $this->gateway->callsTo('subscriptions', 'create'));
        $this->assertTrue($this->user->fresh()->hasActiveSubscription());
    }

    public function test_remote_active_subscription_blocks_second_purchase_even_if_local_state_is_ended()
    {
        $this->purchase();
        $this->source()->update(['status' => 'canceled', 'ended_at' => now()->subDay()]);
        [$id, $setup] = $this->prepare();
        try { $this->billing->purchase($this->user, $this->plan, $id, $setup); $this->fail('Second subscription accepted'); }
        catch (BillingException $exception) { $this->assertStringContainsString('already exists', $exception->getMessage()); }
        $this->assertCount(1, $this->gateway->callsTo('subscriptions', 'create'));
    }

    public function test_ambiguous_old_checkout_never_recreates_subscription_after_idempotency_window()
    {
        [$id, $setup] = $this->prepare();
        DB::table('stripe_billing_operations')->where('id', $id)->update(['created_at' => now()->subDays(2)]);
        $this->expectException(BillingException::class);
        try { $this->billing->purchase($this->user, $this->plan, $id, $setup); }
        finally { $this->assertCount(0, $this->gateway->callsTo('subscriptions', 'create')); }
    }

    public function test_card_update_also_updates_future_owned_schedule_default()
    {
        $this->purchase();
        app(MembershipManagement::class)->change($this->user, (string) Str::uuid(), 'plan', Plan::byName('yearly'));
        [$id, $setup] = $this->prepare('card');
        $this->billing->updateCard($this->user, $id, $setup);
        $this->assertSame('pm_review', $this->gateway->schedules['sub_sched_review']['default_settings']['default_payment_method']);
    }

    public function test_schedule_creation_is_recovered_after_second_provider_call_fails()
    {
        $this->purchase(); $id = (string) Str::uuid();
        $this->gateway->failure = ['subscriptionSchedules.update', new \RuntimeException('temporary schedule failure')];
        try { app(MembershipManagement::class)->change($this->user, $id, 'plan', Plan::byName('yearly')); }
        catch (\RuntimeException $exception) { $this->assertSame('temporary schedule failure', $exception->getMessage()); }
        $this->assertNull($this->source()->pending_plan);
        app(MembershipManagement::class)->change($this->user, $id, 'plan', Plan::byName('yearly'));
        $this->assertSame('yearly', $this->source()->pending_plan);
        $this->assertCount(1, $this->gateway->schedules);
    }

    public function test_new_billing_bookkeeping_is_hidden_from_existing_source_json()
    {
        $this->purchase();
        $json = $this->source()->toArray();
        $this->assertArrayHasKey('stripe_id', $json);
        $this->assertArrayNotHasKey('subscription_id', $json);
        $this->assertArrayNotHasKey('payment_method_id', $json);
        $this->assertArrayNotHasKey('billing_account', $json);
        $this->assertArrayNotHasKey('payment_url', $json);
    }

    public function test_edit_page_renders_without_invoice_requests_and_ended_pages_keep_history()
    {
        $this->purchase(); $this->gateway->calls = [];
        $response = $this->get(route('webapp.membership.edit'))->assertOk()->assertSee('data-membership-payment', false)->assertSee('data-membership-invoices', false);
        if ($directory = getenv('STRIPE_BILLING_PREVIEW')) file_put_contents($directory.'/membership.html', $response->getContent());
        $this->assertCount(0, $this->gateway->calls);
        $this->source()->update(['status' => 'canceled', 'ended_at' => now()]);
        $this->user->unsetRelation('membership');
        $ended = $this->get(route('webapp.membership.edit'))->assertOk()->assertDontSee('data-membership-payment', false)->assertSee('Your membership has ended');
        if ($directory = getenv('STRIPE_BILLING_PREVIEW')) file_put_contents($directory.'/ended.html', $ended->getContent());
    }

    public function test_checkout_uses_exact_trial_length_and_returning_member_wording()
    {
        $this->plan->forceFill(['trial_period_days' => 14])->save();
        $response = $this->get(route('webapp.membership.checkout', $this->plan))->assertOk()->assertSee('14-day free trial')->assertDontSee('createToken', false);
        if ($directory = getenv('STRIPE_BILLING_PREVIEW')) file_put_contents($directory.'/checkout.html', $response->getContent());
        $this->purchase(); $this->source()->update(['status' => 'canceled', 'ended_at' => now()]);
        $this->user->unsetRelation('membership');
        $this->get(route('webapp.membership.checkout', $this->plan))->assertOk()->assertSee('Returning members do not receive another introductory trial.');
    }

    public function test_browser_billing_mutations_require_csrf_even_for_authenticated_accounts()
    {
        $this->withExceptionHandling(); $this->app['env'] = 'review-csrf';
        try {
            foreach (['webapp.membership.prepare.card', 'webapp.membership.cancel', 'webapp.membership.resume', 'webapp.membership.update.card', 'webapp.membership.update.plan', 'webapp.membership.complete'] as $route) {
                $this->withSession(['_token' => 'review-billing-csrf'])->postJson(route($route), ['operation_id' => (string) Str::uuid()])->assertStatus(419);
            }
        } finally { $this->app['env'] = 'testing'; }
        $this->assertCount(0, $this->gateway->calls);
    }

    public function test_selecting_current_plan_removes_future_change_without_resetting_billing()
    {
        $this->purchase(); $management = app(MembershipManagement::class);
        $management->change($this->user, (string) Str::uuid(), 'plan', Plan::byName('yearly'));
        $renewal = $this->source()->renews_at->timestamp;
        $management->change($this->user, (string) Str::uuid(), 'plan', $this->plan);
        $this->assertNull($this->source()->pending_plan);
        $this->assertNull($this->source()->schedule_id);
        $this->assertSame($renewal, $this->source()->renews_at->timestamp);
        $this->assertCount(0, $this->gateway->callsTo('subscriptions', 'update'));
    }

    public function test_swiss_membership_uses_its_own_account_even_with_us_shop_customer()
    {
        $this->purchase();
        DB::table('stripe_billing_profiles')->where('user_id', $this->user->id)->update(['billing_account' => 'swiss']);
        $this->source()->update(['billing_account' => 'swiss']);
        Customer::create(['user_id' => $this->user->id, 'stripe_id' => 'cus_us_shop', 'card_brand' => 'visa', 'card_last_four' => '1111']);
        $this->gateway->calls = [];
        [$id, $setup] = $this->prepare('card');
        $this->billing->updateCard($this->user, $id, $setup);
        $this->assertSame('pk_test_swiss_review', $this->billing->publicKey($this->user));
        foreach ($this->gateway->calls as $call) $this->assertSame('swiss', $call['account']);
    }

    public function test_invoices_use_route_accounts_customer_and_subscription_and_escape_hosted_links()
    {
        $this->purchase();
        $this->gateway->invoices = [['id' => 'in_safe', 'created' => now()->timestamp, 'currency' => 'usd', 'status' => 'open', 'amount_due' => 799, 'amount_paid' => 0,
            'hosted_invoice_url' => 'javascript:alert(1)']];
        $response = $this->getJson(route('webapp.membership.invoices', ['user_id' => 999]))->assertOk();
        $this->assertStringNotContainsString('javascript:', $response->json('html'));
        $call = $this->gateway->callsTo('invoices', 'all')[0];
        $this->assertSame('cus_review', $call['parameters']['customer']);
        $this->assertSame('sub_review', $call['parameters']['subscription']);
        $this->assertSame(12, $call['parameters']['limit']);
    }

    public function test_unpaid_incomplete_checkout_can_be_canceled_without_an_invoice_or_proration()
    {
        [$id] = $this->purchase(['status' => 'incomplete'], false);
        app(MembershipManagement::class)->change($this->user, (string) Str::uuid(), 'cancel');
        $this->assertSame('canceled', $this->source()->status);
        $this->assertFalse($this->user->fresh()->hasActiveSubscription());
        $call = $this->gateway->callsTo('subscriptions', 'cancel')[0];
        $this->assertFalse($call['parameters']['invoice_now']);
        $this->assertFalse($call['parameters']['prorate']);
        $this->assertNull(DB::table('stripe_billing_operations')->where('id', $id)->value('active_key'));
    }
}
