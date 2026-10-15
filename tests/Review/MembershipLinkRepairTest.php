<?php

namespace Tests\Review;

use App\Billing\{Membership, MembershipLinkRepair, Plan};
use App\Billing\Sources\{Apple, Stripe};
use App\Billing\Stripe\{Gateway, MembershipBilling, WebhookProcessor};
use App\Services\Apple\AdminReceiptVerifier;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Event, Mail, Notification, Schema};
use Tests\Support\FakeStripeGateway;

class MembershipLinkRepairTest extends ReviewTestCase
{
    private $plan, $gateway, $appleResults = [], $appleCalls = [];

    public function setUp(): void
    {
        parent::setUp(); Event::fake(); Notification::fake();
        $this->travelTo(carbon('2026-10-15 17:00:00'));
        $this->plan = config('membership-link-repair');
        $this->gateway = new FakeStripeGateway;
        $this->app->instance(Gateway::class, $this->gateway);
        $verifier = \Mockery::mock(AdminReceiptVerifier::class);
        $verifier->shouldReceive('verify')->andReturnUsing(function ($source) {
            $this->appleCalls[] = $source->id;
            return $this->appleResults[$source->id];
        });
        $this->app->instance(AdminReceiptVerifier::class, $verifier);
        foreach ($this->plan['cases'] as $case) {
            Model::withoutEvents(function () use ($case) { create(User::class, ['id' => $case['user'], 'super_user' => false]); });
            foreach ($case['links'] as $id => $spec) {
                if ($spec['type'] === 'apple') {
                    DB::table('apple_memberships')->insert([
                        'id' => $spec['source'], 'plan' => 'com.leftlaneapps.PianoLIT.monthplan',
                        'latest_receipt' => 'private-receipt-'.$id, 'password' => 'private-apple-secret',
                        'latest_receipt_info' => json_encode(['original_transaction_id' => $spec['chain']]),
                        'renews_at' => now()->subYear(), 'validated_at' => now()->subMonth(),
                        'created_at' => now()->subYears(2), 'updated_at' => now()->subMonth(),
                    ]);
                    $this->appleResults[$spec['source']] = ['status' => 'expired', 'environment' => 'Production',
                        'product_id' => 'com.leftlaneapps.PianoLIT.monthplan', 'expires_at' => now()->subMonth(),
                        'grace_expires_at' => null, 'auto_renews' => false];
                } else {
                    $active = $spec['status'] === 'active';
                    DB::table('stripe_memberships')->insert([
                        'id' => $spec['source'], 'stripe_id' => $spec['customer'], 'plan' => 'monthly', 'status' => $spec['status'],
                        'renews_at' => $active ? now()->addMonth() : null, 'ended_at' => $active ? null : now()->subYear(),
                        'created_at' => now()->subYears(2), 'updated_at' => now()->subMonth(),
                    ]);
                    $this->gateway->subscriptions[$spec['subscription']] = ['id' => $spec['subscription'], 'customer' => $spec['customer'],
                        'status' => $spec['status'], 'cancel_at_period_end' => false, 'current_period_end' => now()->addMonth()->timestamp,
                        'ended_at' => $active ? null : now()->subYear()->timestamp];
                }
                DB::table('memberships')->insert(['id' => $id, 'user_id' => $case['user'], 'source_id' => $spec['source'],
                    'source_type' => $spec['type'] === 'apple' ? Apple::class : Stripe::class,
                    'created_at' => now()->subYears(2), 'updated_at' => now()->subMonth()]);
            }
        }
    }

    public function tearDown(): void
    {
        // The production migration intentionally refuses to drop populated
        // archives. These are disposable SQLite fixtures, never billing records.
        if (Schema::hasTable('membership_link_archives')) DB::table('membership_link_archives')->delete();
        parent::tearDown();
    }

    private function runRepair(bool $apply = false): array { return app(MembershipLinkRepair::class)->run($this->plan, $apply); }
    private function links(): array { return DB::table('memberships')->orderBy('id')->get()->map(function ($row) { return (array) $row; })->all(); }
    private function stripeRows(): array { return DB::table('stripe_memberships')->orderBy('id')->get()->map(function ($row) { return (array) $row; })->all(); }

    public function test_preview_verifies_all_sources_without_writing_or_exposing_receipts()
    {
        $links = $this->links(); $stripes = $this->stripeRows();
        $result = $this->runRepair();
        $this->assertSame(0, $result['changed']);
        $this->assertCount(4, $result['rows']);
        $this->assertSame($links, $this->links());
        $this->assertSame($stripes, $this->stripeRows());
        $this->assertDatabaseCount('membership_link_archives', 0);
        $this->assertCount(5, $this->appleCalls);
        $this->assertSame(['swiss', 'swiss', 'us'], array_column($this->gateway->calls, 'account'));
        foreach ($this->gateway->calls as $call) $this->assertSame(['subscriptions', 'all'], [$call['resource'], $call['method']]);
        $this->assertStringNotContainsString('private-', json_encode($result));
        Mail::assertNothingSent(); Mail::assertNothingQueued(); Notification::assertNothingSent();
    }

    public function test_apply_preserves_history_and_source_payloads_and_restores_the_paid_membership()
    {
        $links = collect($this->links())->keyBy('id');
        $apples = DB::table('apple_memberships')->orderBy('id')->get()->toJson();
        $stripes = $this->stripeRows();
        $stripeJson = Stripe::orderBy('id')->get()->toJson();
        $this->assertFalse(User::findOrFail(3830)->hasActiveSubscription());
        $result = $this->runRepair(true);
        $this->assertSame(4, $result['changed']);
        $this->assertSame([149, 189, 295, 347], DB::table('memberships')->orderBy('id')->pluck('id')->all());
        $this->assertSame([53, 215, 285, 348], DB::table('membership_link_archives')->orderBy('membership_id')->pluck('membership_id')->all());
        foreach (DB::table('membership_link_archives')->get() as $archive) {
            $original = $links[$archive->membership_id];
            $this->assertSame((int) $original['user_id'], (int) $archive->user_id);
            $this->assertSame($original['source_type'], $archive->source_type);
            $this->assertSame((int) $original['source_id'], (int) $archive->source_id);
            $this->assertSame($original['created_at'], $archive->original_created_at);
            $this->assertSame($original['updated_at'], $archive->original_updated_at);
            $this->assertStringNotContainsString('private-', $archive->evidence);
        }
        $this->assertSame($apples, DB::table('apple_memberships')->orderBy('id')->get()->toJson());
        foreach ($this->stripeRows() as $index => $row) {
            unset($row['subscription_id'], $row['billing_account']);
            unset($stripes[$index]['subscription_id'], $stripes[$index]['billing_account']);
            $this->assertSame($stripes[$index], $row);
        }
        $this->assertSame($stripeJson, Stripe::orderBy('id')->get()->toJson());
        $this->assertDatabaseCount('apple_memberships', 5); $this->assertDatabaseCount('stripe_memberships', 3);
        $this->assertDatabaseCount('payments', 0);
        $this->assertTrue(User::findOrFail(3830)->hasActiveSubscription());
        $this->assertSame(102, app(MembershipBilling::class)->source(User::findOrFail(3830))->id);
        $this->assertDatabaseHas('stripe_memberships', ['id' => 102, 'subscription_id' => 'sub_1QDVgKA3roHoLcB1IqI98cwr', 'billing_account' => 'us']);
        $this->assertDatabaseHas('stripe_memberships', ['id' => 59, 'subscription_id' => 'sub_1MvTHFGQzApYr7LGEndh0edL', 'billing_account' => 'swiss']);
        $plan = Plan::forceCreate(['name' => 'monthly', 'price' => 799, 'interval' => 'month', 'trial_period_days' => 7, 'description' => 'Review', 'statement_descriptor' => 'PIANOLIT']);
        foreach ([894, 2778, 3830, 4636] as $id) $this->assertSame(0, app(MembershipBilling::class)->trialDays(User::findOrFail($id), $plan));
        Mail::assertNothingSent(); Mail::assertNothingQueued(); Notification::assertNothingSent();
    }

    public function test_repeating_applied_plan_is_a_noop_and_cannot_overwrite_a_later_subscription()
    {
        $this->runRepair(true); $this->gateway->calls = []; $this->appleCalls = [];
        DB::table('stripe_memberships')->where('id', 102)->update(['subscription_id' => 'sub_later']);
        $links = $this->links();
        $this->assertSame(0, $this->runRepair(true)['changed']);
        $this->assertSame($links, $this->links());
        $this->assertDatabaseCount('membership_link_archives', 4);
        $this->assertDatabaseHas('stripe_memberships', ['id' => 102, 'subscription_id' => 'sub_later']);
        $this->assertSame([], $this->gateway->calls); $this->assertSame([], $this->appleCalls);
    }

    public function test_late_charge_for_archived_source_keeps_financial_ownership_without_changing_current_membership()
    {
        $this->runRepair(true);
        $gateway = \Mockery::mock(Gateway::class);
        $gateway->shouldReceive('call')->once()->with('swiss', 'charges', 'retrieve', [], 'ch_archived')->andReturn([
            'id' => 'ch_archived', 'customer' => 'cus_NgqzvTLQ1Wmqn7', 'status' => 'succeeded',
            'amount' => 799, 'amount_refunded' => 100, 'currency' => 'usd',
        ]);
        $this->app->instance(Gateway::class, $gateway);
        app(WebhookProcessor::class)->receive(['id' => 'evt_archivedcharge', 'type' => 'charge.refunded',
            'data' => ['object' => ['id' => 'ch_archived', 'customer' => 'cus_NgqzvTLQ1Wmqn7']]], 'swiss');
        $this->assertDatabaseHas('payments', ['charge_id' => 'ch_archived', 'user_id' => 2778, 'refund' => 100]);
        $this->assertSame(189, User::findOrFail(2778)->membership->id);
        $this->assertFalse(User::findOrFail(2778)->hasActiveSubscription());
        $this->assertSame(2778, Membership::archivedOwnerForSource(Stripe::class, 59));
    }

    public function test_archived_owner_lookup_rejects_ambiguous_or_deleted_owners()
    {
        $this->runRepair(true);
        $row = (array) DB::table('membership_link_archives')->where('membership_id', 215)->first();
        unset($row['id']); $row['membership_id'] = 99999; $row['user_id'] = 3830;
        DB::table('membership_link_archives')->insert($row);
        $this->assertNull(Membership::archivedOwnerForSource(Stripe::class, 59));
        DB::table('membership_link_archives')->where('membership_id', 99999)->delete();
        DB::table('users')->where('id', 2778)->delete();
        $this->assertNull(Membership::archivedOwnerForSource(Stripe::class, 59));
        $this->assertNull(Membership::archivedOwnerForSource(User::class, 59));
    }

    public function test_unexpected_membership_set_is_not_automatically_resolved()
    {
        DB::table('memberships')->insert(['user_id' => 894, 'source_type' => Apple::class, 'source_id' => 999]);
        $links = $this->links();
        try { $this->runRepair(true); $this->fail('Unexpected membership accepted'); }
        catch (\DomainException $e) { $this->assertStringContainsString('Membership set changed', $e->getMessage()); }
        $this->assertSame($links, $this->links()); $this->assertDatabaseCount('membership_link_archives', 0);
        $this->assertSame([], $this->gateway->calls);
    }

    public function test_a_source_shared_with_another_owner_is_not_repaired()
    {
        DB::table('memberships')->insert(['user_id' => 9999, 'source_type' => Apple::class, 'source_id' => 86]);
        $this->expectExceptionMessage('also linked to another account');
        $this->runRepair(true);
    }

    /** @dataProvider invalidAppleEvidence */
    public function test_unknown_active_sandbox_or_renewing_apple_evidence_cannot_be_archived(array $override)
    {
        $this->appleResults[86] = array_replace($this->appleResults[86], $override);
        $links = $this->links();
        try { $this->runRepair(true); $this->fail('Unsafe Apple evidence accepted'); }
        catch (\DomainException $e) { $this->assertNotEmpty($e->getMessage()); }
        $this->assertSame($links, $this->links()); $this->assertDatabaseCount('membership_link_archives', 0);
    }

    public static function invalidAppleEvidence(): array
    {
        return [
            [['error_code' => '21010']], [['status' => 'active']], [['environment' => 'Sandbox']],
            [['auto_renews' => true]], [['auto_renews' => null]], [['expires_at' => null]],
            [['grace_expires_at' => new \Carbon\Carbon('2027-01-01')]], [['expires_at' => new \Carbon\Carbon('2027-01-01')]],
        ];
    }

    public function test_changed_apple_chain_is_rejected_before_receipt_validation()
    {
        DB::table('apple_memberships')->where('id', 86)->update(['latest_receipt_info' => json_encode(['original_transaction_id' => '999'])]);
        try { $this->runRepair(true); $this->fail('Changed chain accepted'); }
        catch (\DomainException $e) { $this->assertStringContainsString('chain changed', $e->getMessage()); }
        $this->assertSame([], $this->appleCalls);
    }

    public function test_reactivated_swiss_subscription_is_not_archived()
    {
        $this->gateway->subscriptions['sub_1MvTHFGQzApYr7LGEndh0edL']['status'] = 'active';
        $links = $this->links();
        try { $this->runRepair(true); $this->fail('Reactivated Swiss subscription accepted'); }
        catch (\DomainException $e) { $this->assertStringContainsString('Stripe subscriptions changed', $e->getMessage()); }
        $this->assertSame($links, $this->links()); $this->assertDatabaseCount('membership_link_archives', 0);
    }

    public function test_a_second_remote_subscription_is_not_ignored()
    {
        $this->gateway->subscriptions['sub_second'] = $this->gateway->subscriptions['sub_1QDVgKA3roHoLcB1IqI98cwr'];
        $this->gateway->subscriptions['sub_second']['id'] = 'sub_second';
        $this->expectExceptionMessage('Stripe subscriptions changed'); $this->runRepair(true);
    }

    /** @dataProvider changedStripeIdentity */
    public function test_stripe_customer_account_and_existing_subscription_identity_are_checked(array $attributes)
    {
        DB::table('stripe_memberships')->where('id', 26)->update($attributes);
        $this->expectExceptionMessage('Stripe identity changed'); $this->runRepair(true);
    }

    public static function changedStripeIdentity(): array
    {
        return [[['billing_account' => 'us']], [['stripe_id' => 'cus_other']], [['subscription_id' => 'sub_other']]];
    }

    public function test_a_subscription_linked_to_another_source_is_not_adopted()
    {
        DB::table('stripe_memberships')->where('id', 59)->update(['subscription_id' => 'sub_1KWdwSGQzApYr7LG9x77MV7a']);
        $this->expectExceptionMessage('already linked to a different source'); $this->runRepair(true);
    }

    public function test_membership_insert_during_provider_verification_aborts_the_repair()
    {
        $this->gateway->intercept = function ($call) {
            if ($call['parameters']['customer'] === 'cus_R5h4enZlR6jpat') {
                DB::table('memberships')->insert(['user_id' => 894, 'source_type' => Apple::class, 'source_id' => 999]);
            }
        };
        try { $this->runRepair(true); $this->fail('Concurrent membership accepted'); }
        catch (\DomainException $e) { $this->assertStringContainsString('Membership set changed', $e->getMessage()); }
        $this->assertDatabaseCount('memberships', 9); $this->assertDatabaseCount('membership_link_archives', 0);
    }

    public function test_receipt_change_during_provider_verification_is_not_overwritten()
    {
        $this->gateway->intercept = function ($call) {
            if ($call['parameters']['customer'] === 'cus_R5h4enZlR6jpat') {
                DB::table('apple_memberships')->where('id', 86)->update(['latest_receipt' => 'new-private-receipt']);
            }
        };
        try { $this->runRepair(true); $this->fail('Concurrent receipt accepted'); }
        catch (\DomainException $e) { $this->assertStringContainsString('source changed', $e->getMessage()); }
        $this->assertDatabaseHas('apple_memberships', ['id' => 86, 'latest_receipt' => 'new-private-receipt']);
        $this->assertDatabaseCount('memberships', 8); $this->assertDatabaseCount('membership_link_archives', 0);
    }

    public function test_verification_that_takes_over_five_minutes_cannot_be_applied()
    {
        $this->gateway->intercept = function ($call) {
            if ($call['parameters']['customer'] === 'cus_R5h4enZlR6jpat') $this->travel(6)->minutes();
        };
        try { $this->runRepair(true); $this->fail('Old verification accepted'); }
        catch (\DomainException $e) { $this->assertStringContainsString('too long', $e->getMessage()); }
        $this->assertDatabaseCount('memberships', 8); $this->assertDatabaseCount('membership_link_archives', 0);
    }

    public function test_late_archive_failure_rolls_back_all_four_cases_and_stripe_links()
    {
        DB::table('membership_link_archives')->insert(['membership_id' => 348, 'user_id' => 4636, 'kept_membership_id' => 347,
            'source_type' => Apple::class, 'source_id' => 260, 'repair_version' => 'other', 'plan_hash' => str_repeat('0', 64),
            'evidence' => '{}', 'archived_at' => now()]);
        $links = $this->links(); $stripes = $this->stripeRows();
        try { $this->runRepair(true); $this->fail('Archive conflict accepted'); }
        catch (\Illuminate\Database\QueryException $e) { $this->assertSame($links, $this->links()); }
        $this->assertSame($stripes, $this->stripeRows()); $this->assertDatabaseCount('membership_link_archives', 1);
    }

    public function test_preview_does_not_require_the_new_archive_migration_but_apply_does()
    {
        Schema::drop('membership_link_archives');
        try {
            $this->assertCount(4, $this->runRepair()['rows']);
            $this->gateway->calls = [];
            try { $this->runRepair(true); $this->fail('Apply accepted without archive'); }
            catch (\DomainException $e) { $this->assertStringContainsString('migration', $e->getMessage()); }
            $this->assertSame([], $this->gateway->calls);
        } finally { (new \CreateMembershipLinkArchivesTable)->up(); }
    }

    public function test_populated_archive_cannot_be_dropped_by_migration_rollback()
    {
        $this->runRepair(true);
        $this->expectExceptionMessage('Restore or preserve the membership link archive');
        (new \CreateMembershipLinkArchivesTable)->down();
    }

    public function test_the_super_user_override_and_account_data_are_not_modified()
    {
        DB::table('users')->where('id', 894)->update(['super_user' => true]);
        $users = DB::table('users')->orderBy('id')->get()->toJson();
        $this->runRepair(true);
        $this->assertSame($users, DB::table('users')->orderBy('id')->get()->toJson());
        $this->assertTrue(User::findOrFail(894)->hasActiveSubscription());
        Mail::assertNothingSent(); Mail::assertNothingQueued(); Notification::assertNothingSent();
    }

    public function test_command_defaults_to_preview_and_apply_is_explicit()
    {
        $this->artisan('memberships:repair-legacy-links')
            ->expectsOutput('PREVIEW ONLY — no database writes; checking Stripe and Apple.')->assertExitCode(0);
        $this->assertDatabaseCount('memberships', 8);
        $this->artisan('memberships:repair-legacy-links', ['--apply' => true])->assertExitCode(0);
        $this->assertDatabaseCount('memberships', 4);
    }

    public function test_command_redacts_provider_failure_messages()
    {
        $this->gateway->failure = ['subscriptions.all', new \RuntimeException('sk_live_private private-receipt')];
        $this->artisan('memberships:repair-legacy-links', ['--apply' => true])
            ->expectsOutput('Stripe verification failed for membership 149 (RuntimeException).')->assertExitCode(1);
        $this->assertDatabaseCount('memberships', 8); $this->assertDatabaseCount('membership_link_archives', 0);
    }

    public function test_invalid_plan_is_rejected_before_provider_requests()
    {
        $this->plan['cases'][0]['links'][53]['type'] = 'users';
        $this->expectExceptionMessage('Invalid reviewed source identity');
        try { $this->runRepair(true); }
        finally { $this->assertSame([], $this->gateway->calls); $this->assertSame([], $this->appleCalls); }
    }
}
