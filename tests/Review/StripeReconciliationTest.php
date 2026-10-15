<?php

namespace Tests\Review;

use App\Billing\Membership;
use App\Billing\Sources\Stripe;
use App\Billing\Sources\Concerns\StripeSwissCustomers;
use App\Billing\Stripe\{Accounts, Gateway};
use App\User;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Event, Mail, Notification};
use Tests\Support\FakeStripeGateway;

class StripeReconciliationTest extends ReviewTestCase
{
    private $gateway, $reported = [];

    public function setUp(): void
    {
        parent::setUp(); Event::fake(); Notification::fake();
        $this->travelTo(carbon('2026-10-15 18:30:00'));
        $this->gateway = new FakeStripeGateway;
        $this->app->instance(Gateway::class, $this->gateway);
        $handler = \Mockery::mock(ExceptionHandler::class);
        $handler->shouldReceive('report')->andReturnUsing(function ($exception) { $this->reported[] = get_class($exception); });
        $this->app->instance(ExceptionHandler::class, $handler);
        Model::withoutEvents(function () { create(User::class, ['id' => 2911, 'super_user' => false]); });
        DB::table('stripe_memberships')->insert([
            'id' => 119, 'stripe_id' => 'cus_TSpfX6Kbrjbo2Z', 'plan' => 'yearly', 'status' => 'active',
            'renews_at' => '2026-11-28 08:03:23', 'card_brand' => 'visa', 'card_last_four' => '4242',
            'created_at' => now()->subYear(), 'updated_at' => now()->subDay(), 'synced_at' => now()->subHour(),
        ]);
        DB::table('memberships')->insert(['id' => 335, 'user_id' => 2911, 'source_type' => Stripe::class, 'source_id' => 119]);
        $this->gateway->subscriptions['sub_1SVtzjGQzApYr7LGeYQJe8vo'] = [
            'id' => 'sub_1SVtzjGQzApYr7LGeYQJe8vo', 'customer' => 'cus_TSpfX6Kbrjbo2Z', 'status' => 'active',
            'current_period_end' => carbon('2026-11-28 08:03:23')->timestamp,
            'items' => ['data' => [['id' => 'si_review', 'price' => ['id' => 'yearly']]]],
            'cancel_at_period_end' => false, 'ended_at' => null, 'pause_collection' => null,
        ];
    }

    private function source(): array { return (array) DB::table('stripe_memberships')->where('id', 119)->first(); }

    private function events(): array
    {
        DB::table('stripe_webhook_events')->insert([
            ['billing_account' => 'us', 'event_id' => 'evt_pending', 'type' => 'customer.updated',
                'payload' => json_encode(['type' => 'customer.updated', 'data' => ['object' => ['id' => 'cus_unrelated']]]),
                'created_at' => now(), 'updated_at' => now(), 'processed_at' => null],
            ['billing_account' => 'us', 'event_id' => 'evt_old', 'type' => 'customer.updated',
                'payload' => '{"retained":"history"}', 'created_at' => now()->subDays(100),
                'updated_at' => now()->subDays(100), 'processed_at' => now()->subDays(100)],
        ]);
        return DB::table('stripe_webhook_events')->orderBy('id')->get()->map(function ($row) { return (array) $row; })->all();
    }

    public function test_verified_exception_routes_only_the_modern_membership_client_to_swiss()
    {
        $source = Stripe::findOrFail(119);
        $this->assertSame('swiss', app(Accounts::class)->forSource($source));
        $this->assertFalse(in_array($source->stripe_id, (new StripeSwissCustomers)->get(), true));
        $this->assertSame('us', app(Accounts::class)->forSource(new Stripe(['stripe_id' => 'cus_unlisted'])));
        $source->billing_account = 'us';
        $this->assertSame('us', app(Accounts::class)->forSource($source));
        $this->assertSame([], $this->gateway->calls);
    }

    public function test_targeted_reconciliation_links_the_selected_source_without_touching_other_sources_or_events()
    {
        DB::table('stripe_memberships')->insert(['id' => 120, 'stripe_id' => 'cus_other', 'plan' => 'monthly', 'status' => 'active']);
        $other = (array) DB::table('stripe_memberships')->where('id', 120)->first();
        $events = $this->events(); $source = $this->source();
        $links = DB::table('memberships')->get()->toJson();
        $users = DB::table('users')->get()->toJson();
        $provider = $this->gateway->subscriptions;
        $key = \Stripe\Stripe::getApiKey(); $version = \Stripe\Stripe::getApiVersion();
        $this->artisan('stripe:reconcile', ['--source' => 119])
            ->expectsOutput('Reconciled source 119 using swiss / sub_1SVtzjGQzApYr7LGeYQJe8vo.')->assertExitCode(0);
        $linked = $this->source();
        $this->assertSame('swiss', $linked['billing_account']);
        $this->assertSame('sub_1SVtzjGQzApYr7LGeYQJe8vo', $linked['subscription_id']);
        foreach (['stripe_id', 'plan', 'status', 'renews_at', 'ended_at', 'canceled_at', 'membership_ends_at', 'paused_at', 'card_brand', 'card_last_four'] as $field) {
            $this->assertSame($source[$field], $linked[$field], $field);
        }
        $this->assertEquals($other, (array) DB::table('stripe_memberships')->where('id', 120)->first());
        $this->assertEquals($events, DB::table('stripe_webhook_events')->orderBy('id')->get()->map(function ($row) { return (array) $row; })->all());
        $this->assertSame($links, DB::table('memberships')->get()->toJson());
        $this->assertSame($users, DB::table('users')->get()->toJson());
        $this->assertSame($provider, $this->gateway->subscriptions);
        $this->assertSame(['swiss', 'swiss', 'swiss'], array_column($this->gateway->calls, 'account'));
        foreach ($this->gateway->calls as $call) $this->assertContains($call['method'], ['all', 'retrieve']);
        $json = Stripe::findOrFail(119)->toArray();
        $this->assertArrayNotHasKey('subscription_id', $json); $this->assertArrayNotHasKey('billing_account', $json);
        $this->assertSame($key, \Stripe\Stripe::getApiKey()); $this->assertSame($version, \Stripe\Stripe::getApiVersion());
        $this->assertSame([], $this->reported);
        Mail::assertNothingSent(); Mail::assertNothingQueued(); Notification::assertNothingSent();
    }

    public function test_targeted_refresh_failure_rolls_back_identity_and_dates_without_advancing_rotation()
    {
        $source = $this->source(); $events = $this->events();
        $this->gateway->failure = ['customers.retrieve', new \RuntimeException('private provider details')];
        $this->artisan('stripe:reconcile', ['--source' => 119])
            ->expectsOutput('Stripe reconciliation finished; 1 item(s) need retry or review.')->assertExitCode(1);
        $this->assertSame($source, $this->source());
        $this->assertEquals($events, DB::table('stripe_webhook_events')->orderBy('id')->get()->map(function ($row) { return (array) $row; })->all());
        $this->assertSame([\RuntimeException::class], $this->reported);
    }

    public function test_targeted_reconciliation_rejects_a_source_shared_by_two_owners_before_provider_reads()
    {
        Model::withoutEvents(function () { create(User::class, ['id' => 2912]); });
        DB::table('memberships')->insert(['user_id' => 2912, 'source_type' => Stripe::class, 'source_id' => 119]);
        $source = $this->source();
        $this->artisan('stripe:reconcile', ['--source' => 119])->assertExitCode(1);
        $this->assertSame($source, $this->source()); $this->assertSame([], $this->gateway->calls);
    }

    public function test_targeted_reconciliation_does_not_update_a_replacement_source_when_the_selected_link_changes()
    {
        DB::table('stripe_memberships')->insert(['id' => 120, 'stripe_id' => 'cus_replacement', 'plan' => 'monthly', 'status' => 'active']);
        $billing = \Mockery::mock(\App\Billing\Stripe\MembershipBilling::class);
        $billing->shouldReceive('source')->once()->andReturn(Stripe::findOrFail(120));
        $this->app->instance(\App\Billing\Stripe\MembershipBilling::class, $billing);
        $source = $this->source();
        $this->artisan('stripe:reconcile', ['--source' => 119])->assertExitCode(1);
        $this->assertSame($source, $this->source()); $this->assertSame([], $this->gateway->calls);
        $this->assertDatabaseHas('stripe_memberships', ['id' => 120, 'subscription_id' => null, 'synced_at' => null]);
    }

    /** @dataProvider missingOwnership */
    public function test_targeted_missing_ownership_is_reported_without_recreating_an_account_or_changing_source($removeUser)
    {
        if ($removeUser) DB::table('users')->where('id', 2911)->delete();
        else DB::table('memberships')->where('source_id', 119)->delete();
        $source = $this->source();
        $this->artisan('stripe:reconcile', ['--source' => 119])
            ->expectsOutput('The selected source has no existing account owner; its records were left unchanged.')->assertExitCode(1);
        $this->assertSame($source, $this->source()); $this->assertSame([], $this->gateway->calls);
    }

    public static function missingOwnership(): array { return [[false], [true]]; }

    /** @dataProvider invalidSource */
    public function test_invalid_source_selection_stops_before_provider_or_event_processing($value)
    {
        $source = $this->source(); $events = $this->events();
        $this->artisan('stripe:reconcile', ['--source' => $value])->assertExitCode(1);
        $this->assertSame($source, $this->source()); $this->assertSame([], $this->gateway->calls);
        $this->assertEquals($events, DB::table('stripe_webhook_events')->orderBy('id')->get()->map(function ($row) { return (array) $row; })->all());
    }

    public static function invalidSource(): array { return [[null], [''], ['0'], ['-1'], ['abc'], ['119.5'], ['119 OR 1=1']]; }

    public function test_targeted_reconciliation_rejects_multiple_live_provider_subscriptions()
    {
        $this->gateway->subscriptions['sub_another'] = $this->gateway->subscriptions['sub_1SVtzjGQzApYr7LGeYQJe8vo'];
        $this->gateway->subscriptions['sub_another']['id'] = 'sub_another';
        $source = $this->source();
        $this->artisan('stripe:reconcile', ['--source' => 119])->assertExitCode(1);
        $this->assertSame($source, $this->source());
        $this->assertCount(1, $this->gateway->calls);
        $this->assertSame('all', $this->gateway->calls[0]['method']);
    }

    public function test_targeted_reconciliation_rejects_a_subscription_belonging_to_another_customer()
    {
        DB::table('stripe_memberships')->where('id', 119)->update(['subscription_id' => 'sub_1SVtzjGQzApYr7LGeYQJe8vo']);
        $this->gateway->subscriptions['sub_1SVtzjGQzApYr7LGeYQJe8vo']['customer'] = 'cus_someone_else';
        $source = $this->source();
        $this->artisan('stripe:reconcile', ['--source' => 119])->assertExitCode(1);
        $this->assertSame($source, $this->source());
    }

    public function test_missing_source_is_reported_without_processing_webhook_events()
    {
        $events = $this->events();
        $this->artisan('stripe:reconcile', ['--source' => 999])
            ->expectsOutput('Stripe membership source 999 does not exist.')->assertExitCode(1);
        $this->assertSame([], $this->gateway->calls);
        $this->assertEquals($events, DB::table('stripe_webhook_events')->orderBy('id')->get()->map(function ($row) { return (array) $row; })->all());
    }

    public function test_audit_cannot_be_combined_with_a_targeted_state_refresh()
    {
        $source = $this->source();
        $this->artisan('stripe:reconcile', ['--source' => 119, '--audit' => true])
            ->expectsOutput('Use --audit without --source for the read-only database audit.')->assertExitCode(1);
        $this->assertSame($source, $this->source()); $this->assertSame([], $this->gateway->calls);
    }

    public function test_default_reconciliation_still_processes_events_and_redacts_old_completed_payloads()
    {
        $this->events();
        $this->artisan('stripe:reconcile')->assertExitCode(0);
        $this->assertNotNull(DB::table('stripe_webhook_events')->where('event_id', 'evt_pending')->value('processed_at'));
        $this->assertSame('{}', DB::table('stripe_webhook_events')->where('event_id', 'evt_old')->value('payload'));
        $this->assertDatabaseHas('stripe_memberships', ['id' => 119, 'billing_account' => 'swiss', 'subscription_id' => 'sub_1SVtzjGQzApYr7LGeYQJe8vo']);
    }
}
