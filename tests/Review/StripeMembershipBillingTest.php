<?php

namespace Tests\Review;

use App\Billing\{Customer, Membership, Payment, Plan};
use App\Billing\Sources\{Apple, Stripe};
use App\Billing\Stripe\{Accounts, BillingException, Gateway, MembershipBilling, MembershipManagement, Operations, WebhookProcessor};
use App\User;
use App\Mail\Memberships\MembershipConfirmed;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache, DB, Event, Mail, Notification, Redis};
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

    private function endedCustomer(): void
    {
        $this->purchase();
        $this->gateway->subscriptions['sub_review']['status'] = 'canceled';
        $this->gateway->subscriptions['sub_review']['ended_at'] = now()->subDay()->timestamp;
        $this->source()->update(['status' => 'canceled', 'ended_at' => now()->subDay()]);
        $this->user->unsetRelation('membership');
        $this->gateway->calls = [];
    }

    public function test_ended_membership_settings_keeps_history_and_offers_new_checkout_with_saved_card()
    {
        $this->endedCustomer();
        $this->get(route('webapp.settings'))->assertOk()->assertSee(route('webapp.membership.edit'), false)->assertSee('My membership');
        $details = $this->get(route('webapp.membership.edit'))->assertOk()->assertSee('data-saved-card', false)->assertSee('data-checkout-open', false);
        $dom = new \DOMDocument; @$dom->loadHTML($details->getContent()); $xpath = new \DOMXPath($dom);
        $this->assertSame(2, $xpath->query('//*[@id="update-plan-tab"]//a[@data-checkout-open]')->length);
        $this->assertSame(0, $xpath->query('//*[@id="update-plan-tab"]//button[@disabled]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="payment-checkout-modal"]')->length);
        $checkout = $this->getJson(route('webapp.membership.checkout', $this->plan))->assertOk()->json('html');
        $dom = new \DOMDocument; @$dom->loadHTML($checkout); $xpath = new \DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//button[@data-checkout-saved-card and @aria-pressed="true"]')->length);
        $this->assertSame(1, $xpath->query('//button[@data-payment-card-add]')->length);
        $this->assertSame(1, $xpath->query('//fieldset[@data-checkout-new-fields and @hidden and @disabled]')->length);
        $this->assertSame(1, $xpath->query('//*[@data-checkout-card-check]')->length);
        $this->assertStringContainsString('4444', $checkout);
        $this->assertStringNotContainsString('pm_review', $checkout);
        $this->assertCount(0, $this->gateway->calls, 'Settings, history and checkout rendering do not contact Stripe.');
    }

    public function test_saved_card_purchase_verifies_ownership_and_reuses_customer_without_a_new_setup_intent()
    {
        $this->endedCustomer();
        $card = $this->billing->savedCard($this->user);
        $id = (string) Str::uuid();
        $prepared = $this->postJson(route('webapp.membership.prepare', $this->plan), [
            'operation_id' => $id, 'use_saved_card' => true, 'saved_card_reference' => $card['reference'],
        ])->assertOk()->assertJson(['saved_card' => true])->json();
        $this->gateway->nextSubscription = ['id' => 'sub_returning', 'status' => 'active'];
        $this->postJson(route('webapp.membership.purchase', $this->plan), [
            'operation_id' => $prepared['operation_id'], 'use_saved_card' => true, 'saved_card_reference' => $card['reference'],
        ])->assertOk()->assertJson(['complete' => true]);
        // Retrying an already created checkout returns that subscription instead of creating another.
        $this->billing->purchase($this->user, $this->plan, $id, null, true, $card['reference']);
        $this->assertCount(0, $this->gateway->callsTo('setupIntents', 'create'));
        $this->assertCount(0, $this->gateway->callsTo('customers', 'create'));
        $calls = $this->gateway->callsTo('subscriptions', 'create');
        $this->assertCount(1, $calls);
        $this->assertSame('pm_review', $calls[0]['parameters']['default_payment_method']);
        $this->assertSame('cus_review', $calls[0]['parameters']['customer']);
        $this->assertArrayNotHasKey('trial_period_days', $calls[0]['parameters']);
        $this->assertSame('sub_returning', $this->source()->subscription_id);
        $this->billing->finish($this->user, $id);
        $this->assertTrue($this->user->fresh()->hasActiveSubscription());
    }

    public function test_ended_member_can_confirm_card_removal_without_losing_history_and_retries_keep_a_new_card()
    {
        $this->endedCustomer();
        $reference = $this->billing->savedCard($this->user)['reference'];
        $details = $this->get(route('webapp.membership.edit'))->assertOk();
        $dom = new \DOMDocument; @$dom->loadHTML($details->getContent()); $xpath = new \DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//*[@id="update-card-tab"]//button[@data-bs-target="#membership-remove-card-confirmation"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="membership-remove-card-confirmation"]//form[@method="POST"]//input[@name="_token"]')->length);
        $this->assertSame('body', $xpath->query('//*[@id="membership-remove-card-confirmation"]')->item(0)->parentNode->nodeName);
        $this->assertCount(0, $this->gateway->calls);
        $before = $this->source()->only(['stripe_id', 'subscription_id', 'plan', 'status', 'renews_at', 'ended_at', 'membership_ends_at']);
        $membership = DB::table('memberships')->get()->toJson();
        $payments = DB::table('payments')->get()->toJson();
        $id = (string) Str::uuid();
        $this->post(route('webapp.membership.remove.card'), ['operation_id' => $id, 'saved_card_reference' => $reference,
            'payment_source_id' => 'pm_untrusted', 'user_id' => 999999])->assertRedirect(route('webapp.membership.edit').'#update-card-tab');
        $this->assertFalse((bool) $this->source()->hasCard());
        $this->assertNull($this->source()->payment_method_id);
        $this->assertEquals($before, $this->source()->only(array_keys($before)));
        $this->assertSame($membership, DB::table('memberships')->get()->toJson());
        $this->assertSame($payments, DB::table('payments')->get()->toJson());
        $this->assertSame('pm_review', $this->gateway->callsTo('paymentMethods', 'detach')[0]['id']);
        $this->assertDatabaseHas('stripe_billing_operations', ['id' => $id, 'state' => 'complete', 'payment_source_id' => 'pm_review']);
        $this->get(route('webapp.membership.edit'))->assertOk()->assertSee('No card on file.')->assertDontSee('membership-remove-card-confirmation');
        $checkout = $this->getJson(route('webapp.membership.checkout', $this->plan))->assertOk()->json('html');
        $this->assertStringNotContainsString('data-checkout-saved-card', $checkout);
        $this->billing->refreshCard($this->source(), $this->gateway->subscriptions['sub_review']);
        $this->assertFalse((bool) $this->source()->hasCard(), 'A later provider refresh cannot resurrect the detached card.');
        $this->assertNull($this->source()->payment_method_id);
        $this->source()->update(['payment_method_id' => 'pm_new', 'card_brand' => 'mastercard', 'card_last_four' => '9999']);
        $this->post(route('webapp.membership.remove.card'), ['operation_id' => $id, 'saved_card_reference' => $reference])->assertRedirect(route('webapp.membership.edit').'#update-card-tab');
        $this->assertSame('pm_new', $this->source()->payment_method_id);
        $this->assertCount(1, $this->gateway->callsTo('paymentMethods', 'detach'));
        Mail::assertNothingQueued();
    }

    /** @dataProvider removableMembershipStates */
    public function test_card_removal_is_available_without_canceling_or_changing_the_membership(array $changes)
    {
        $this->purchase();
        $this->source()->update($changes);
        $this->user->unsetRelation('membership');
        $this->gateway->calls = [];
        $details = $this->get(route('webapp.membership.edit'))->assertOk()
            ->assertSee('Removing this card does not cancel your membership.')
            ->assertSee('Add another card before your next payment');
        $dom = new \DOMDocument; @$dom->loadHTML($details->getContent()); $xpath = new \DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//*[@id="update-card-tab"]//button[@data-bs-target="#membership-remove-card-confirmation"]')->length);
        $this->assertSame('body', $xpath->query('//*[@id="membership-remove-card-confirmation"]')->item(0)->parentNode->nodeName);
        $this->assertCount(0, $this->gateway->calls, 'Displaying the option never changes or fetches provider data.');
        $before = $this->source()->only(['stripe_id', 'subscription_id', 'plan', 'status', 'renews_at', 'ended_at', 'membership_ends_at', 'canceled_at', 'paused_at']);
        $access = $this->user->fresh()->hasActiveSubscription();
        $memberships = DB::table('memberships')->get()->toJson();
        $payments = DB::table('payments')->get()->toJson();
        $subscriptions = $this->gateway->subscriptions;
        $this->post(route('webapp.membership.remove.card'), ['operation_id' => (string) Str::uuid(),
            'saved_card_reference' => $this->billing->savedCard($this->user)['reference']])->assertRedirect(route('webapp.membership.edit').'#update-card-tab');
        $this->assertFalse((bool) $this->source()->hasCard());
        $this->assertNull($this->source()->payment_method_id);
        $this->assertEquals($before, $this->source()->only(array_keys($before)));
        $this->assertSame($access, $this->user->fresh()->hasActiveSubscription());
        $this->assertSame($memberships, DB::table('memberships')->get()->toJson());
        $this->assertSame($payments, DB::table('payments')->get()->toJson());
        $this->assertSame($subscriptions, $this->gateway->subscriptions);
        $mutations = array_values(array_filter($this->gateway->calls, function ($call) { return in_array($call['method'], ['create', 'update', 'cancel', 'delete', 'detach'], true); }));
        $this->assertCount(1, $mutations);
        $this->assertSame('paymentMethods', $mutations[0]['resource']);
        $this->assertSame('detach', $mutations[0]['method']);
        $this->get(route('webapp.membership.edit'))->assertOk()->assertSee('No card on file.')
            ->assertSee('data-bs-target="#membership-card-editor"', false)->assertDontSee('membership-remove-card-confirmation');
        $this->billing->refreshCard($this->source(), $this->gateway->subscriptions['sub_review']);
        $this->assertFalse((bool) $this->source()->hasCard());
        Mail::assertNothingQueued();
    }

    public static function removableMembershipStates(): array
    {
        return [
            'active' => [['status' => 'active']],
            'trial' => [['status' => 'trialing']],
            'grace period' => [['canceled_at' => '2020-01-01', 'membership_ends_at' => '2099-01-01']],
            'paused' => [['paused_at' => '2020-01-01']],
            'past due' => [['status' => 'past_due']],
            'legacy subscription identity' => [['subscription_id' => null]],
        ];
    }

    public function test_card_removal_still_refuses_another_live_or_ambiguous_subscription()
    {
        $this->purchase();
        $this->gateway->subscriptions['sub_other'] = array_replace($this->gateway->subscriptions['sub_review'], ['id' => 'sub_other', 'status' => 'active']);
        foreach (['sub_review', null] as $subscriptionId) {
            $this->source()->update(['subscription_id' => $subscriptionId]);
            $this->postJson(route('webapp.membership.remove.card'), ['operation_id' => (string) Str::uuid(),
                'saved_card_reference' => $this->billing->savedCard($this->user)['reference']])->assertStatus(422)
                ->assertJsonFragment(['message' => 'Another subscription uses this billing customer. Please contact contact@pianolit.com to remove this card.']);
        }
        unset($this->gateway->subscriptions['sub_review']);
        $this->source()->update(['subscription_id' => 'sub_review']);
        $this->postJson(route('webapp.membership.remove.card'), ['operation_id' => (string) Str::uuid(),
            'saved_card_reference' => $this->billing->savedCard($this->user)['reference']])->assertStatus(422);
        $this->assertCount(0, $this->gateway->callsTo('paymentMethods', 'detach'));
        $this->assertTrue((bool) $this->source()->hasCard());
    }

    public function test_removal_rejects_stale_foreign_and_shared_cards_without_mutation()
    {
        $this->endedCustomer();
        $reference = $this->billing->savedCard($this->user)['reference'];
        $this->postJson(route('webapp.membership.remove.card'), ['operation_id' => (string) Str::uuid(), 'saved_card_reference' => str_repeat('a', 64)])->assertStatus(422);
        $this->assertCount(0, $this->gateway->calls);
        $this->gateway->paymentMethods['pm_review'] = ['id' => 'pm_review', 'customer' => 'cus_foreign', 'type' => 'card', 'card' => ['brand' => 'visa', 'last4' => '4444']];
        $this->postJson(route('webapp.membership.remove.card'), ['operation_id' => (string) Str::uuid(), 'saved_card_reference' => $reference])->assertStatus(422);
        unset($this->gateway->paymentMethods['pm_review']);
        Customer::create(['user_id' => $this->user->id, 'stripe_id' => 'cus_review']);
        $this->postJson(route('webapp.membership.remove.card'), ['operation_id' => (string) Str::uuid(), 'saved_card_reference' => $reference])->assertStatus(422);
        $this->assertCount(0, $this->gateway->callsTo('paymentMethods', 'detach'));
        $this->assertTrue((bool) $this->source()->hasCard());
    }

    public function test_removal_recovers_provider_success_after_a_local_failure_without_detaching_twice()
    {
        $this->endedCustomer();
        $id = (string) Str::uuid(); $reference = $this->billing->savedCard($this->user)['reference'];
        DB::unprepared("CREATE TRIGGER reject_review_card_removal BEFORE UPDATE OF card_brand ON stripe_memberships WHEN NEW.card_brand IS NULL BEGIN SELECT RAISE(FAIL, 'review card removal rollback'); END");
        $this->postJson(route('webapp.membership.remove.card'), ['operation_id' => $id, 'saved_card_reference' => $reference])->assertStatus(422);
        $this->assertTrue((bool) $this->source()->hasCard());
        $this->assertDatabaseHas('stripe_billing_operations', ['id' => $id, 'state' => 'pending', 'payment_source_id' => 'pm_review']);
        $this->assertNull($this->gateway->paymentMethods['pm_review']['customer']);
        DB::unprepared('DROP TRIGGER reject_review_card_removal');
        $this->post(route('webapp.membership.remove.card'), ['operation_id' => $id, 'saved_card_reference' => $reference])->assertRedirect(route('webapp.membership.edit').'#update-card-tab');
        $this->assertFalse((bool) $this->source()->hasCard());
        $this->assertCount(1, $this->gateway->callsTo('paymentMethods', 'detach'));
    }

    public function test_legacy_swiss_removal_retries_only_its_original_source_when_customer_default_changes()
    {
        $this->endedCustomer();
        $this->source()->update(['billing_account' => 'swiss', 'payment_method_id' => null]);
        DB::table('stripe_billing_profiles')->where('user_id', $this->user->id)->update(['billing_account' => 'swiss']);
        $this->gateway->sources['card_old'] = ['id' => 'card_old', 'object' => 'card', 'customer' => 'cus_review', 'brand' => 'visa', 'last4' => '4444'];
        $this->gateway->customers['cus_review']['default_source'] = $this->gateway->sources['card_old'];
        $id = (string) Str::uuid(); $reference = $this->billing->savedCard($this->user)['reference'];
        $this->gateway->failure = ['customerSources.delete', new \RuntimeException('review provider interruption')];
        $this->postJson(route('webapp.membership.remove.card'), ['operation_id' => $id, 'saved_card_reference' => $reference])->assertStatus(422);
        $this->assertDatabaseHas('stripe_billing_operations', ['id' => $id, 'state' => 'pending', 'payment_source_id' => 'card_old']);
        $this->gateway->sources['card_other'] = ['id' => 'card_other', 'object' => 'card', 'customer' => 'cus_review', 'brand' => 'mastercard', 'last4' => '9999'];
        $this->gateway->customers['cus_review']['default_source'] = $this->gateway->sources['card_other'];
        $this->post(route('webapp.membership.remove.card'), ['operation_id' => $id, 'saved_card_reference' => $reference])->assertRedirect(route('webapp.membership.edit').'#update-card-tab');
        $this->assertArrayNotHasKey('card_old', $this->gateway->sources);
        $this->assertArrayHasKey('card_other', $this->gateway->sources);
        foreach ($this->gateway->callsTo('customerSources', 'delete') as $call) {
            $this->assertSame('card_old', $call['id']); $this->assertSame('swiss', $call['account']); $this->assertSame('cus_review', $call['parameters']['customer']);
        }
        $this->assertFalse((bool) $this->source()->hasCard());
    }

    public function test_interrupted_removal_cannot_remove_a_replacement_card_and_authentication_is_required()
    {
        $this->endedCustomer();
        $id = (string) Str::uuid(); $reference = $this->billing->savedCard($this->user)['reference'];
        $this->gateway->failure = ['paymentMethods.detach', new \RuntimeException('review provider interruption')];
        $this->postJson(route('webapp.membership.remove.card'), ['operation_id' => $id, 'saved_card_reference' => $reference])->assertStatus(422);
        $this->source()->update(['payment_method_id' => 'pm_new', 'card_last_four' => '9999']);
        $this->postJson(route('webapp.membership.remove.card'), ['operation_id' => $id, 'saved_card_reference' => $reference])->assertStatus(422);
        $this->assertSame('pm_new', $this->source()->payment_method_id);
        $this->assertCount(1, $this->gateway->callsTo('paymentMethods', 'detach'));
        auth('web')->logout();
        $this->withExceptionHandling()->postJson(route('webapp.membership.remove.card'), ['operation_id' => $id, 'saved_card_reference' => $reference])->assertUnauthorized();
    }

    public function test_removing_an_already_detached_stored_card_only_clears_its_local_display()
    {
        $this->endedCustomer();
        $this->gateway->paymentMethods['pm_review'] = ['id' => 'pm_review', 'customer' => null, 'type' => 'card', 'card' => ['brand' => 'visa', 'last4' => '4444']];
        $this->post(route('webapp.membership.remove.card'), ['operation_id' => (string) Str::uuid(),
            'saved_card_reference' => $this->billing->savedCard($this->user)['reference']])->assertRedirect(route('webapp.membership.edit').'#update-card-tab');
        $this->assertFalse((bool) $this->source()->hasCard());
        $this->assertCount(0, $this->gateway->callsTo('paymentMethods', 'detach'));
    }

    public function test_legacy_card_source_consumed_response_and_retry_preserve_the_verified_removal_target()
    {
        $this->endedCustomer();
        $this->source()->update(['payment_method_id' => null]);
        $source = ['id' => 'src_review', 'object' => 'source', 'type' => 'card', 'customer' => 'cus_review',
            'status' => 'chargeable', 'card' => ['brand' => 'visa', 'last4' => '4444']];
        $this->gateway->sources['src_review'] = $source;
        $this->gateway->customers['cus_review']['default_source'] = $source;
        $id = (string) Str::uuid(); $reference = $this->billing->savedCard($this->user)['reference'];
        DB::unprepared("CREATE TRIGGER reject_review_card_removal BEFORE UPDATE OF card_brand ON stripe_memberships WHEN NEW.card_brand IS NULL BEGIN SELECT RAISE(FAIL, 'review source removal rollback'); END");
        $this->postJson(route('webapp.membership.remove.card'), ['operation_id' => $id, 'saved_card_reference' => $reference])->assertStatus(422);
        $this->assertSame('consumed', $this->gateway->sources['src_review']['status']);
        $this->assertDatabaseHas('stripe_billing_operations', ['id' => $id, 'state' => 'pending', 'payment_source_id' => 'src_review']);
        DB::unprepared('DROP TRIGGER reject_review_card_removal');
        $this->post(route('webapp.membership.remove.card'), ['operation_id' => $id, 'saved_card_reference' => $reference])->assertRedirect(route('webapp.membership.edit').'#update-card-tab');
        $this->assertFalse((bool) $this->source()->hasCard());
        $this->assertCount(1, $this->gateway->callsTo('customerSources', 'delete'));
    }

    public function test_saved_card_rejects_foreign_detached_stale_and_missing_methods_before_subscription_creation()
    {
        $this->endedCustomer();
        $reference = $this->billing->savedCard($this->user)['reference'];
        foreach ([['customer' => 'cus_foreign', 'type' => 'card'], ['customer' => null, 'type' => 'card'], ['customer' => 'cus_review', 'type' => 'sepa_debit']] as $method) {
            $this->gateway->paymentMethods['pm_review'] = $method + ['id' => 'pm_review', 'card' => ['last4' => '4444']];
            $response = $this->postJson(route('webapp.membership.prepare', $this->plan), [
                'operation_id' => (string) Str::uuid(), 'use_saved_card' => true, 'saved_card_reference' => $reference,
            ])->assertStatus(422);
            $this->assertStringContainsString('no longer available', $response->json('message'));
        }
        unset($this->gateway->paymentMethods['pm_review']);
        $this->source()->update(['payment_method_id' => 'pm_changed']);
        $this->postJson(route('webapp.membership.prepare', $this->plan), [
            'operation_id' => (string) Str::uuid(), 'use_saved_card' => true, 'saved_card_reference' => $reference,
        ])->assertStatus(422)->assertJson(['message' => 'Your saved card has changed. Reload checkout or add a new card.']);
        $this->assertCount(0, $this->gateway->callsTo('subscriptions', 'create'));
        $this->assertCount(0, $this->gateway->callsTo('setupIntents', 'create'));
    }

    public function test_saved_card_is_rechecked_at_purchase_and_cannot_bypass_card_update_verification()
    {
        $this->endedCustomer();
        $reference = $this->billing->savedCard($this->user)['reference'];
        $prepared = $this->billing->prepare($this->user, (string) Str::uuid(), 'purchase', $this->plan, null, true, $reference);
        $this->gateway->paymentMethods['pm_review'] = ['id' => 'pm_review', 'customer' => 'cus_foreign', 'type' => 'card', 'card' => ['last4' => '4444']];
        $this->postJson(route('webapp.membership.purchase', $this->plan), [
            'operation_id' => $prepared['operation_id'], 'use_saved_card' => true, 'saved_card_reference' => $reference,
        ])->assertStatus(422);
        $this->withExceptionHandling()->postJson(route('webapp.membership.update.card'), [
            'operation_id' => $prepared['operation_id'], 'use_saved_card' => true,
        ])->assertStatus(422)->assertJsonValidationErrors('setup_intent');
        $this->assertCount(0, $this->gateway->callsTo('subscriptions', 'create'));
    }

    public function test_saved_card_legacy_reference_uses_only_its_own_customers_default_in_the_saved_account()
    {
        $this->endedCustomer();
        $this->source()->update(['payment_method_id' => null, 'billing_account' => 'swiss']);
        DB::table('stripe_billing_profiles')->where('user_id', $this->user->id)->update(['billing_account' => 'swiss']);
        $this->gateway->customers['cus_review']['invoice_settings']['default_payment_method'] = 'pm_review';
        $reference = $this->billing->savedCard($this->user)['reference'];
        $result = $this->billing->prepare($this->user, (string) Str::uuid(), 'purchase', $this->plan, null, true, $reference);
        $this->assertTrue($result['saved_card']);
        $this->assertSame('swiss', $this->gateway->callsTo('paymentMethods', 'retrieve')[0]['account']);
        $this->assertSame('cus_review', $this->gateway->callsTo('customers', 'retrieve')[0]['id']);
        $this->assertCount(0, $this->gateway->callsTo('customers', 'create'));
        $this->source()->update(['payment_method_id' => null]);
        $this->gateway->customers['cus_review']['invoice_settings']['default_payment_method'] = null;
        $this->postJson(route('webapp.membership.prepare', $this->plan), [
            'operation_id' => $result['operation_id'], 'use_saved_card' => true, 'saved_card_reference' => $reference,
        ])->assertStatus(422)->assertJson(['message' => 'This saved card needs to be entered again. Please add a new card.']);
    }

    public function test_resubscribing_keeps_earlier_membership_customer_invoices()
    {
        $this->endedCustomer();
        $reference = $this->billing->savedCard($this->user)['reference'];
        $prepared = $this->billing->prepare($this->user, (string) Str::uuid(), 'purchase', $this->plan, null, true, $reference);
        $this->gateway->nextSubscription = ['id' => 'sub_next', 'status' => 'active'];
        $this->billing->purchase($this->user, $this->plan, $prepared['operation_id'], null, true, $reference);
        $this->gateway->invoices = [
            ['id' => 'in_previous', 'customer' => 'cus_review', 'subscription' => 'sub_review'],
            ['id' => 'in_current', 'customer' => 'cus_review', 'subscription' => 'sub_next'],
            ['id' => 'in_foreign', 'customer' => 'cus_foreign', 'subscription' => 'sub_other'],
        ];
        $result = app(MembershipManagement::class)->invoices($this->user);
        $this->assertSame(['in_previous', 'in_current'], array_column($result['invoices'], 'id'));
        $this->assertSame('sub_next', $this->gateway->callsTo('invoices', 'createPreview')[0]['parameters']['subscription']);
    }

    public function test_saved_card_request_without_membership_never_provisions_a_customer_or_operation()
    {
        $this->postJson(route('webapp.membership.prepare', $this->plan), [
            'operation_id' => (string) Str::uuid(), 'use_saved_card' => true, 'saved_card_reference' => str_repeat('a', 64),
        ])->assertStatus(422);
        $this->assertCount(0, $this->gateway->calls);
        $this->assertDatabaseCount('stripe_billing_operations', 0);
        $this->assertDatabaseCount('stripe_billing_profiles', 0);
    }

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

    public function test_plan_change_preserves_writable_tax_settings_without_response_only_fields()
    {
        $this->purchase();
        $source = $this->source();
        $schedule = $this->gateway->call('us', 'subscriptionSchedules', 'create', ['from_subscription' => $source->subscription_id]);
        $this->gateway->schedules[$schedule['id']]['metadata'] = ['pianolit_membership' => (string) $source->id];
        $this->gateway->schedules[$schedule['id']]['phases'][0]['automatic_tax'] = [
            'enabled' => true, 'disabled_reason' => null,
            'liability' => ['type' => 'account', 'account' => ['id' => 'acct_tax', 'object' => 'account']],
        ];
        app(MembershipManagement::class)->change($this->user, (string) Str::uuid(), 'plan', Plan::byName('yearly'));
        $phases = $this->gateway->callsTo('subscriptionSchedules', 'update')[0]['parameters']['phases'];
        foreach ($phases as $phase) {
            $this->assertSame(['enabled' => true, 'liability' => ['type' => 'account', 'account' => 'acct_tax']], $phase['automatic_tax']);
        }
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
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame(0, $xpath->query('//*[@id="overview-tab"]//*[contains(@class,"membership-plan-panel")]')->length);
        $this->assertSame(2, $xpath->query('//*[@id="update-plan-tab"]//*[@data-plan-card]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="membership-tabs"]//*[@aria-selected="true"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="membership-tabs" and @data-tab-navigation]')->length);
        $frame = $xpath->query('//*[@data-tab-navigation-frame][.//*[@id="membership-tabs"]]')->item(0);
        $this->assertNotNull($frame);
        $this->assertSame(4, $xpath->query('.//*[@role="tabpanel"]', $frame)->length);
        $this->assertSame('overview-tab', $xpath->query('//*[@id="membership-tabs"]')->item(0)->getAttribute('data-tab-default'));
        $html = $response->getContent();
        $this->assertLessThan(strpos($html, 'id="membership-tabs"'), strpos($html, 'PianoLITTabNavigation.prepare('));
        $this->assertGreaterThan(strpos($html, 'id="invoices-tab"'), strpos($html, 'PianoLITTabNavigation.initial('));
        $this->assertLessThan(strpos($html, 'src="'.mix('js/app.js').'"'), strpos($html, 'PianoLITTabNavigation.initial('));
        $this->assertSame(1, $xpath->query('//*[@id="overview-tab"]//a[@href="#update-card-tab" and @data-membership-tab]')->length);
        $this->assertSame(1, $xpath->query('//button[@name="plan" and @data-name="monthly" and @disabled]')->length);
        $this->assertSame(1, $xpath->query('//button[@name="plan" and @data-name="yearly" and not(@disabled)]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="update-plan-action"]//form[@method="POST"]//input[@name="operation_id"]')->length);
        if ($directory = getenv('STRIPE_BILLING_PREVIEW')) file_put_contents($directory.'/membership.html', $response->getContent());
        $this->assertCount(0, $this->gateway->calls);
        $this->source()->update(['status' => 'canceled', 'ended_at' => now()]);
        $this->user->unsetRelation('membership');
        $ended = $this->get(route('webapp.membership.edit'))->assertOk()->assertDontSee('data-membership-payment', false)->assertSee('Your membership has ended');
        if ($directory = getenv('STRIPE_BILLING_PREVIEW')) file_put_contents($directory.'/ended.html', $ended->getContent());
    }

    public function test_billing_shows_saved_card_add_editor_and_cancellation_without_inner_tabs_or_a_pause_offer()
    {
        $this->purchase(); $this->gateway->calls = [];
        $response = $this->get(route('webapp.membership.edit'))->assertOk();
        $dom = new \DOMDocument; @$dom->loadHTML($response->getContent()); $xpath = new \DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//*[@id="update-card-tab"]//*[@data-saved-card]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="update-card-tab"]//button[@data-payment-card-add and @aria-controls="membership-card-editor" and @data-bs-toggle="modal" and @data-bs-target="#membership-card-editor" and @aria-haspopup="dialog"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="membership-card-editor" and contains(@class,"modal") and @data-payment-card-editor and @aria-hidden="true" and @aria-labelledby="membership-card-editor-title" and @aria-describedby="membership-card-description"]//form[@data-deferred-card and @data-split-card]')->length);
        $this->assertSame('body', $xpath->query('//*[@id="membership-card-editor"]')->item(0)->parentNode->nodeName);
        $this->assertSame(0, $xpath->query('//*[@id="update-card-tab"]//*[@id="update-card-form"]')->length, 'The card editor is outside the tab/page container.');
        $this->assertSame(1, $xpath->query('//*[@id="membership-card-editor"]//button[@data-bs-dismiss="modal" and @data-payment-card-cancel]')->length);
        $this->assertSame(3, $xpath->query('//*[@id="update-card-form"]//*[@data-stripe-field]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="update-card-form"]//*[@data-card-preview]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="update-card-form"]//input[@name="cardholder" and @autocomplete="cc-name" and contains(@class,"pianolit-input")]')->length);
        $this->assertSame(0, $xpath->query('//*[@id="update-card-tab"]//*[@data-bs-toggle="list"]')->length);
        $this->assertSame(0, $xpath->query('//*[@id="update-card-tab"]//input[@name="paused" and @value="1"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="membership-cancel-confirmation" and contains(@class,"modal")]//form[@id="cancel-membership-form" and @method="POST"]//input[@name="operation_id"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="update-card-tab"]//button[@type="button" and @data-bs-toggle="modal" and @data-bs-target="#membership-cancel-confirmation"]')->length);
        $this->assertSame(0, $xpath->query('//*[@id="update-card-tab"]//*[@id="cancel-membership-form"]')->length, 'The confirmation lives outside the tab/page container.');
        $this->assertSame(1, $xpath->query('//*[@id="membership-cancel-confirmation" and @tabindex="-1" and @aria-labelledby="membership-cancel-confirmation-title" and @aria-describedby="membership-cancel-description"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="membership-cancel-confirmation"]//button[@type="button" and @data-bs-dismiss="modal" and contains(., "Keep membership")]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="membership-cancel-confirmation"]//form[@action="'.route('webapp.membership.cancel').'"]//input[@name="_token"]')->length);
        $this->assertSame(1, $xpath->query('//*[@id="membership-cancel-confirmation"]//button[@type="submit" and contains(., "Confirm cancellation")]')->length);
        $this->assertCount(0, $this->gateway->calls);

        $this->source()->update(['paused_at' => now()]); $this->user->unsetRelation('membership');
        $this->get(route('webapp.membership.edit'))->assertOk()->assertSee('Resume billing')->assertDontSee('Pause my membership')->assertSee('value="0"', false);
        $this->source()->update(['paused_at' => null, 'canceled_at' => now(), 'membership_ends_at' => now()->addDays(5)]); $this->user->unsetRelation('membership');
        $this->get(route('webapp.membership.edit'))->assertOk()->assertSee('Your membership is ending')->assertSee('Keep my membership')->assertDontSee('Confirm cancellation');
        $this->source()->update(['card_brand' => null, 'card_last_four' => null]); $this->user->unsetRelation('membership');
        $this->get(route('webapp.membership.edit'))->assertOk()->assertSee('No card on file')->assertDontSee('data-saved-card', false)->assertSee('Add new card');
        $this->assertCount(0, $this->gateway->calls);
    }

    public function test_renewal_summary_and_cancel_modal_show_saved_dates_without_promising_unpaid_access()
    {
        $this->purchase();
        $renewal = now()->addMonth()->startOfDay();
        $this->source()->update(['status' => 'active', 'renews_at' => $renewal]);
        $this->user->unsetRelation('membership');
        $this->gateway->calls = [];
        $response = $this->get(route('webapp.membership.edit'))->assertOk();
        $dom = new \DOMDocument; @$dom->loadHTML($response->getContent()); $xpath = new \DOMXPath($dom);
        $summary = $xpath->query('//section[@aria-labelledby="membership-renewal-heading"]')->item(0)->textContent;
        $this->assertStringContainsString('Auto-renew on', $summary);
        $this->assertStringContainsString('Monthly Premium', $summary);
        $this->assertStringContainsString('Next renewal', $summary);
        $this->assertStringContainsString($renewal->toFormattedDateString(), $summary);
        $dialog = $xpath->query('//*[@id="membership-cancel-confirmation"]')->item(0)->textContent;
        $this->assertStringContainsString('You’ll keep full access until your current billing period ends.', $dialog);
        $this->assertStringContainsString($renewal->toFormattedDateString(), $dialog);
        $this->assertSame('body', $xpath->query('//*[@id="membership-cancel-confirmation"]')->item(0)->parentNode->nodeName);

        $this->source()->update(['status' => 'trialing']); $this->user->unsetRelation('membership');
        $this->get(route('webapp.membership.edit'))->assertOk()->assertSee('First payment')->assertSee('Trial access ends')->assertSee('You’ll keep full access until your trial ends.');
        foreach (['incomplete', 'past_due', 'unpaid'] as $status) {
            $this->source()->update(['status' => $status]); $this->user->unsetRelation('membership');
            $response = $this->get(route('webapp.membership.edit'))->assertOk()->assertDontSee('Auto-renew on')->assertDontSee('You’ll keep full access');
            if ($status === 'incomplete') $response->assertSee('Your unpaid checkout will be canceled immediately.');
            else $response->assertSee('Any outstanding invoices remain available in Invoices.');
        }
        $this->source()->update(['status' => 'active', 'renews_at' => now()->subDay()]); $this->user->unsetRelation('membership');
        $this->get(route('webapp.membership.edit'))->assertOk()->assertDontSee('Auto-renew on')->assertDontSee('You’ll keep full access');
        $this->source()->update(['renews_at' => $renewal, 'paused_at' => now()]); $this->user->unsetRelation('membership');
        $this->get(route('webapp.membership.edit'))->assertOk()->assertSee('Billing paused')->assertSee('Resume billing')->assertDontSee('Auto-renew on')->assertDontSee('You’ll keep full access');
        $this->source()->update(['paused_at' => null, 'canceled_at' => now(), 'membership_ends_at' => now()->addDays(2)]); $this->user->unsetRelation('membership');
        $this->get(route('webapp.membership.edit'))->assertOk()->assertSee('Renewal off')->assertDontSee('id="membership-cancel-confirmation"', false);
        $this->assertCount(0, $this->gateway->calls, 'The summary and dialog only use saved billing state.');
    }

    public function test_billing_actions_return_to_billing_after_cancellation_or_resuming()
    {
        $this->purchase();
        foreach (['webapp.membership.cancel', 'webapp.membership.resume'] as $route) {
            $this->post(route($route), ['operation_id' => (string) Str::uuid()])->assertRedirect(route('webapp.membership.edit').'#update-card-tab');
        }
        app(MembershipManagement::class)->change($this->user, (string) Str::uuid(), 'pause');
        $this->post(route('webapp.membership.update.billing-status'), ['operation_id' => (string) Str::uuid(), 'paused' => '0'])
            ->assertRedirect(route('webapp.membership.edit').'#update-card-tab');
    }

    public function test_edit_page_respects_an_expired_access_end_with_a_stale_future_renewal()
    {
        $this->purchase();
        $this->source()->update(['status' => 'active', 'membership_ends_at' => now()->subDay(), 'renews_at' => now()->addMonth()]);
        $this->user->unsetRelation('membership');
        $this->gateway->calls = [];
        $this->get(route('webapp.membership.edit'))->assertOk()->assertSee('Your membership has ended')->assertSee('Membership ended')->assertDontSee('data-membership-payment', false);
        $this->assertCount(0, $this->gateway->calls);
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

    public function test_pricing_shows_real_billing_totals_and_only_offers_trials_to_new_members()
    {
        auth('web')->logout();
        $this->get(route('webapp.membership.pricing'))->assertOk()
            ->assertSee('$89.99')->assertSee('$7.50')->assertSee('Save 6%')
            ->assertSee('Start 7-day free trial')->assertSee('billed yearly, after your free trial.');
        $this->actingAs($this->user, 'web');
        $this->purchase(); $this->source()->update(['status' => 'canceled', 'ended_at' => now()]);
        $this->user->unsetRelation('membership');
        $this->get(route('webapp.membership.pricing'))->assertOk()
            ->assertSee('Choose Yearly')->assertDontSee('Start 7-day free trial');
    }

    private function cacheDiscovery(): void
    {
        Redis::shouldReceive('get')->andReturn(null);
        Cache::put('app.discover'.\App\Api\Api::WEB_DISCOVER_CACHE_SUFFIX, collect([
            ['title' => 'Free weekly pick', 'row' => 'gallery', 'type' => 'piece', 'content' => []],
            ['title' => 'Composers', 'row' => 'gallery', 'type' => 'piece', 'content' => []],
            ['title' => 'Latest pieces', 'row' => 'gallery', 'type' => 'piece', 'content' => []],
        ]), 60);
    }

    private function renderConfirmation(MembershipConfirmed $mail): string
    {
        $mail->build();
        return (string) app(\Illuminate\Mail\Markdown::class)->render($mail->markdown, $mail->buildViewData());
    }

    public function test_completed_checkout_redirects_home_with_a_one_time_confirmation_and_one_email()
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-10-16 12:00:00'));
        $this->cacheDiscovery();
        [$id] = $this->purchase([], false);
        foreach ([1, 2] as $attempt) {
            $this->postJson(route('webapp.membership.complete'), ['operation_id' => $id, 'email' => 'someone-else@example.com'])
                ->assertOk()->assertExactJson(['redirect' => route('webapp.discover')])
                ->assertSessionHas('membership-confirmation.user_id', $this->user->id)
                ->assertSessionHas('membership-confirmation.email_queued', true);
        }
        Mail::assertQueued(MembershipConfirmed::class, function ($mail) {
            return $mail->hasTo($this->user->email) && count($mail->to) === 1 && !$mail->cc && !$mail->bcc
                && $mail->plan === 'Monthly Premium' && $mail->trial && $mail->renewalDate;
        });
        Mail::assertQueued(MembershipConfirmed::class, 1);

        $response = $this->get(route('webapp.discover'))->assertOk()
            ->assertSee('id="membership-confirmation-modal"', false)->assertSee('You’re all set!')
            ->assertSee('Monthly Premium')->assertSee('Free trial')->assertSee('Trial ends')->assertSee('Confirmation sent to your email');
        $details = $response->viewData('membershipConfirmation');
        $this->assertSame('Monthly Premium', $details['plan']);
        $this->assertSame('Free trial', $details['status']);
        $this->assertSame('2026-10-16', $details['purchased_at']->toDateString());
        $this->assertSame('2026-10-23', $details['trial_ends_at']->toDateString());
        $this->assertSame('2026-10-23', $details['next_charge_at']->toDateString(), 'Trial billing starts on the verified trial end, even if a saved period end differs.');
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertCount(1, $xpath->query('//*[@id="membership-confirmation-modal"]//button[@data-bs-dismiss="modal" and contains(., "Start exploring")]'));
        $this->assertCount(1, $xpath->query('//*[@id="membership-confirmation-modal"]//button'));
        $this->assertCount(5, $xpath->query('//*[@id="membership-confirmation-modal"]//dl/div/dt//*[contains(@class,"app-icon")]'));
        $this->assertCount(3, $xpath->query('//*[@id="membership-confirmation-modal"]//dl//time'));
        $this->get(route('webapp.discover'))->assertOk()->assertDontSee('id="membership-confirmation-modal"', false);
        Mail::assertQueued(MembershipConfirmed::class, 1);
    }

    public function test_paid_yearly_confirmation_uses_saved_plan_and_email_has_real_billing_details()
    {
        $this->cacheDiscovery();
        $this->plan = Plan::byName('yearly');
        [$id] = $this->purchase(['status' => 'active'], false);
        $this->postJson(route('webapp.membership.complete'), ['operation_id' => $id, 'plan' => 'monthly'])->assertOk();
        $this->get(route('webapp.discover'))->assertOk()->assertSee('Yearly Premium');
        Mail::assertQueued(MembershipConfirmed::class, function ($mail) {
            $html = $this->renderConfirmation($mail);
            $this->assertStringContainsString('Your next renewal is', $html);
            $this->assertStringContainsString($this->source()->renews_at->toFormattedDateString(), $html);
            $this->assertStringContainsString(route('webapp.discover'), $html);
            $this->assertStringContainsString(route('webapp.membership.edit'), $html);
            $this->assertStringNotContainsString('free trial', $html);
            $this->assertStringNotContainsString('sub_review', $html);
            $this->assertStringNotContainsString('4444', $html);
            return !$mail->trial && $mail->plan === 'Yearly Premium';
        });
        $trial = $this->renderConfirmation(new MembershipConfirmed('Jamie', 'monthly', true, 'Oct 22, 2026'));
        $this->assertStringContainsString('No payment was due today.', $trial);
        $this->assertStringContainsString('Oct 22, 2026', $trial);
        $this->assertStringContainsString('Cancel before then', $trial);
    }

    public function test_returning_confirmation_uses_the_new_purchase_date_and_paid_renewal_without_trial_rows()
    {
        $this->cacheDiscovery();
        $this->travelTo(\Carbon\Carbon::parse('2026-09-16 12:00:00'));
        $this->endedCustomer();
        $this->assertSame('2026-09-16', $this->source()->created_at->toDateString());
        $this->travelTo(\Carbon\Carbon::parse('2026-10-16 12:00:00'));
        $this->plan = Plan::byName('yearly');
        $reference = $this->billing->savedCard($this->user)['reference'];
        $prepared = $this->billing->prepare($this->user, (string) Str::uuid(), 'purchase', $this->plan, null, true, $reference);
        $this->gateway->nextSubscription = ['id' => 'sub_returning', 'status' => 'active', 'current_period_end' => now()->addYear()->timestamp];
        $this->billing->purchase($this->user, $this->plan, $prepared['operation_id'], null, true, $reference);
        $this->postJson(route('webapp.membership.complete'), ['operation_id' => $prepared['operation_id'],
            'purchased_at' => now()->subYears(10)->timestamp, 'trial_ends_at' => now()->addYears(10)->timestamp])->assertOk();
        $calls = count($this->gateway->calls);
        $response = $this->get(route('webapp.discover'))->assertOk()->assertSee('Yearly Premium')->assertSee('Purchase date')->assertSee('Next charge')->assertDontSee('Trial ends');
        $details = $response->viewData('membershipConfirmation');
        $this->assertSame('Active', $details['status']);
        $this->assertSame('2026-10-16', $details['purchased_at']->toDateString());
        $this->assertSame('2027-10-16', $details['next_charge_at']->toDateString());
        $this->assertFalse($details['trial']);
        $this->assertNull($details['trial_ends_at']);
        $this->assertCount($calls, $this->gateway->calls, 'The success modal only reads saved and verified checkout data.');
    }

    public function test_confirmation_does_not_apply_dates_from_a_different_subscription_or_promise_a_canceled_charge()
    {
        $this->cacheDiscovery();
        $this->travelTo(\Carbon\Carbon::parse('2026-10-16 12:00:00'));
        $this->purchase();
        $this->source()->update(['canceled_at' => now(), 'membership_ends_at' => now()->addWeek()]);
        $this->user->unsetRelation('membership');
        $response = $this->withSession(['membership-confirmation' => ['user_id' => $this->user->id,
            'subscription_id' => 'sub_previous', 'purchased_at' => now()->subYears(10)->timestamp,
            'trial_ends_at' => now()->addYears(10)->timestamp]])->get(route('webapp.discover'))->assertOk()->assertSee('Not scheduled');
        $details = $response->viewData('membershipConfirmation');
        $this->assertSame('Ending soon', $details['status']);
        $this->assertNull($details['purchased_at']);
        $this->assertNull($details['next_charge_at']);
        $this->assertTrue($details['trial_ends_at']->equalTo($this->source()->membership_ends_at), 'A scheduled trial end takes precedence over a stale renewal date.');
    }

    public function test_disabled_membership_email_does_not_queue_or_claim_delivery_after_checkout()
    {
        app(\App\Mail\AutomaticEmailSettings::class)->setEnabled('membership-confirmation', false, null);
        $this->cacheDiscovery();
        [$id] = $this->purchase([], false);
        $this->postJson(route('webapp.membership.complete'), ['operation_id' => $id])
            ->assertOk()->assertExactJson(['redirect' => route('webapp.discover')])
            ->assertSessionHas('membership-confirmation.email_queued', false);
        Mail::assertNothingQueued();
        $this->assertTrue($this->user->fresh()->hasActiveSubscription());
        $this->get(route('webapp.discover'))->assertOk()->assertSee('Your membership details are in')->assertDontSee('Confirmation sent to your email');
    }

    public function test_incomplete_or_foreign_checkout_never_confirms_membership_or_queues_email()
    {
        [$id] = $this->purchase(['status' => 'incomplete'], false);
        $this->postJson(route('webapp.membership.complete'), ['operation_id' => $id])->assertUnprocessable()
            ->assertSessionMissing('membership-confirmation');
        $other = Model::withoutEvents(function () { return create(User::class)->setAppends(['full_name']); });
        $this->actingAs($other, 'web');
        $this->postJson(route('webapp.membership.complete'), ['operation_id' => $id])->assertUnprocessable()
            ->assertSessionMissing('membership-confirmation');
        Mail::assertNothingQueued();
    }

    public function test_legacy_welcome_redirects_and_confirmation_cannot_be_requested_by_url_or_another_account()
    {
        $this->cacheDiscovery();
        $this->get(route('webapp.membership.success'))->assertRedirect(route('webapp.discover'));
        $this->purchase(); $this->user->unsetRelation('membership');
        $this->get(route('webapp.discover', ['welcome' => 1]))->assertOk()->assertViewHas('membershipConfirmation', null);
        $this->withSession(['membership-confirmation' => ['user_id' => $this->user->id + 1, 'email_queued' => true]])
            ->get(route('webapp.discover'))->assertOk()->assertViewHas('membershipConfirmation', null);
        $this->withSession(['membership-confirmation' => ['user_id' => $this->user->id, 'email_queued' => true]])
            ->get(route('webapp.membership.success'))->assertRedirect(route('webapp.discover'));
        $this->get(route('webapp.discover'))->assertOk()->assertSee('Monthly Premium');
        $this->source()->update(['status' => 'canceled', 'ended_at' => now()]); $this->user->unsetRelation('membership');
        $this->withSession(['membership-confirmation' => ['user_id' => $this->user->id]])
            ->get(route('webapp.discover'))->assertOk()->assertViewHas('membershipConfirmation', null);
        auth('web')->logout();
        $this->withSession(['membership-confirmation' => ['user_id' => $this->user->id]])
            ->get(route('webapp.discover'))->assertOk()->assertViewHas('membershipConfirmation', null);
        Mail::assertNothingQueued();
    }

    public function test_email_enqueue_failure_does_not_fail_a_completed_checkout_or_claim_email_was_sent()
    {
        $this->cacheDiscovery();
        [$id] = $this->purchase([], false);
        Mail::shouldReceive('to')->once()->with($this->user->email)->andThrow(new \RuntimeException('Queue unavailable'));
        $this->postJson(route('webapp.membership.complete'), ['operation_id' => $id])->assertOk()
            ->assertExactJson(['redirect' => route('webapp.discover')])->assertSessionHas('membership-confirmation.email_queued', false);
        $this->assertTrue($this->user->fresh()->hasActiveSubscription());
        $this->get(route('webapp.discover'))->assertOk()->assertSee('Your membership details are in')
            ->assertDontSee('Confirmation sent to your email');
    }

    public function test_ajax_checkout_returns_private_account_owned_modal_without_provider_mutations()
    {
        $response = $this->getJson(route('webapp.membership.checkout', $this->plan))->assertOk()->assertJsonStructure(['html']);
        $html = $response->json('html');
        $this->assertStringContainsString('id="payment-checkout-modal"', $html);
        $this->assertStringContainsString('fullscreen-modal', $html);
        $this->assertStringContainsString('data-checkout-key="monthly"', $html);
        $this->assertStringContainsString('Order summary', $html);
        $this->assertStringContainsString(e($this->user->email), $html);
        $this->assertStringContainsString('data-card-preview', $html);
        $this->assertStringNotContainsString('<html', $html);
        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertCount(0, $this->gateway->calls);
        $this->assertDatabaseCount('stripe_billing_operations', 0);
        auth('web')->logout();
        $this->withExceptionHandling()->getJson(route('webapp.membership.checkout', $this->plan))->assertUnauthorized();
    }

    public function test_checkout_restore_url_is_built_only_from_a_known_plan()
    {
        $this->get(route('webapp.membership.pricing', ['checkout' => 'monthly']))->assertOk()
            ->assertViewHas('checkoutUrl', route('webapp.membership.checkout', $this->plan));
        $this->get(route('webapp.membership.pricing', ['checkout' => 'https://example.test/untrusted']))->assertOk()->assertViewHas('checkoutUrl', null);
        $this->get(route('webapp.membership.pricing', ['checkout' => ['monthly']]))->assertOk()->assertViewHas('checkoutUrl', null);
        $this->endedCustomer();
        $this->get(route('webapp.membership.edit', ['checkout' => 'monthly']))->assertOk()
            ->assertViewHas('checkoutUrl', route('webapp.membership.checkout', $this->plan));
        $this->get(route('webapp.membership.edit', ['checkout' => 'https://example.test/untrusted']))->assertOk()->assertViewHas('checkoutUrl', null);
        $this->get(route('webapp.membership.edit', ['checkout' => ['monthly']]))->assertOk()->assertViewHas('checkoutUrl', null);
        $this->source()->update(['status' => 'active', 'ended_at' => null]);
        $this->user->unsetRelation('membership');
        $this->get(route('webapp.membership.edit', ['checkout' => 'monthly']))->assertOk()->assertViewHas('checkoutUrl', null);
    }

    public function test_checkout_keeps_one_shared_fullscreen_close_outside_the_loading_and_payment_content()
    {
        $pages = [
            $this->get(route('webapp.membership.pricing'))->assertOk()->getContent(),
            $this->get(route('webapp.membership.checkout', $this->plan))->assertOk()->getContent(),
            $this->getJson(route('webapp.membership.checkout', $this->plan))->assertOk()->json('html'),
        ];
        foreach ($pages as $html) {
            $dom = new \DOMDocument;
            @$dom->loadHTML($html);
            $xpath = new \DOMXPath($dom);
            $buttons = $xpath->query('//*[@id="payment-checkout-modal"]//button[@aria-label="Close checkout"]');
            $this->assertCount(1, $buttons);
            $close = $buttons->item(0);
            $this->assertStringContainsString('btn btn-secondary fullscreen-modal-close', $close->getAttribute('class'));
            $this->assertStringContainsString('modal-dialog', $close->parentNode->getAttribute('class'));
            $this->assertSame('modal', $close->getAttribute('data-bs-dismiss'));
            $this->assertSame('true', $close->getAttribute('data-checkout-cancel'));
            $this->assertStringNotContainsString('checkout-top-cancel', $html);
        }
        $this->assertCount(0, $this->gateway->calls);
    }

    public function test_browser_billing_mutations_require_csrf_even_for_authenticated_accounts()
    {
        $this->withExceptionHandling(); $this->app['env'] = 'review-csrf';
        try {
            foreach (['webapp.membership.prepare.card', 'webapp.membership.cancel', 'webapp.membership.resume', 'webapp.membership.update.card', 'webapp.membership.remove.card', 'webapp.membership.update.plan', 'webapp.membership.complete'] as $route) {
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

    public function test_invoices_use_authenticated_membership_customer_across_subscriptions_and_escape_hosted_links()
    {
        $this->purchase();
        $this->gateway->invoices = [['id' => 'in_safe', 'created' => now()->timestamp, 'currency' => 'usd', 'status' => 'open', 'amount_due' => 799, 'amount_paid' => 0,
            'hosted_invoice_url' => 'javascript:alert(1)']];
        $response = $this->getJson(route('webapp.membership.invoices', ['user_id' => 999]))->assertOk();
        $this->assertStringNotContainsString('javascript:', $response->json('html'));
        $call = $this->gateway->callsTo('invoices', 'all')[0];
        $this->assertSame('cus_review', $call['parameters']['customer']);
        $this->assertArrayNotHasKey('subscription', $call['parameters'], 'Renewing membership must retain invoices from older subscriptions on this membership customer.');
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
