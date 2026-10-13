<?php

namespace Tests\Review;

use App\{Admin, User};
use App\Analytics\MembershipReport;
use App\Billing\Sources\{Apple, Stripe};
use App\Services\Apple\{AdminReceiptVerifier, MembershipMonitor};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache, DB, Http, Schema};

class AppleMembershipMonitorTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->travelTo(carbon('2026-10-13 12:00:00'));
    }

    private function apple(array $attributes = [], array $account = [])
    {
        $user = Model::withoutEvents(function () use ($account) { return create(User::class, $account); });
        $source = Apple::create($attributes + [
            'plan' => 'com.leftlaneapps.PianoLIT.monthlyplan', 'latest_receipt' => 'test-receipt-'.$user->id,
            'password' => 'test-secret', 'latest_receipt_info' => json_encode(['original_transaction_id' => 'original-1']),
            'renews_at' => now()->subMonth(), 'validated_at' => now()->subMonth(),
        ]);
        $user->membership()->create(['source_type' => Apple::class, 'source_id' => $source->id]);
        return [$user, $source];
    }

    private function transaction(array $attributes = [])
    {
        return $attributes + [
            'product_id' => 'com.leftlaneapps.PianoLIT.monthlyplan', 'original_transaction_id' => 'original-1',
            'expires_date_ms' => (string) (now()->addMonth()->getTimestamp() * 1000), 'is_trial_period' => 'false',
        ];
    }

    private function response(array $transactions = null, array $attributes = [])
    {
        return $attributes + ['status' => 0, 'environment' => 'Production',
            'receipt' => ['bundle_id' => config('apple-membership-monitor.bundle_id')],
            'latest_receipt_info' => $transactions ?? [$this->transaction()]];
    }

    private function report(array $filters = [])
    {
        return (new MembershipReport)->get(Request::create('/analytics/memberships', 'GET', $filters));
    }

    private function fakeHttp($responses)
    {
        // Laravel 8 merges fake callbacks; replace the isolation suite's default fake.
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake($responses);
    }

    public function test_latest_matching_renewal_is_selected_without_treating_false_as_a_trial()
    {
        [, $source] = $this->apple();
        $this->fakeHttp(['*' => Http::response($this->response([
            $this->transaction(['expires_date_ms' => (string) (now()->subDay()->getTimestamp() * 1000), 'is_trial_period' => 'true']),
            $this->transaction(['product_id' => 'different-plan', 'original_transaction_id' => 'other', 'expires_date_ms' => (string) (now()->addYears(5)->getTimestamp() * 1000)]),
            $this->transaction(),
        ]))]);
        $result = (new AdminReceiptVerifier)->verify($source);
        $this->assertSame('active', $result['status']);
        $this->assertTrue($result['expires_at']->equalTo(now()->addMonth()));
    }

    public function test_monitor_persists_evidence_and_does_not_change_mobile_billing_or_access()
    {
        [$user, $source] = $this->apple([], ['super_user' => true]);
        $before = $source->fresh()->getRawOriginal();
        $this->fakeHttp(['*' => Http::response($this->response())]);
        $this->assertSame(['checked' => 1, 'verified' => 1, 'failed' => 0], (new MembershipMonitor)->run());
        $this->assertSame($before, $source->fresh()->getRawOriginal());
        $this->assertTrue($user->fresh()->hasActiveSubscription());
        $this->assertDatabaseHas('apple_membership_checks', ['apple_membership_id' => $source->id, 'status' => 'active', 'failures' => 0]);
        $this->assertSame(1, $this->report()['totals']['active']);
        Http::assertSent(function ($request) { return $request['receipt-data'] && $request['password'] === 'test-secret'; });
    }

    public function test_a_rejected_receipt_does_not_stop_other_memberships_or_claim_expiry()
    {
        [, $bad] = $this->apple(); [, $good] = $this->apple();
        $this->fakeHttp(function ($request) use ($bad) { return Http::response($request['receipt-data'] === $bad->latest_receipt ? ['status' => 21010] : $this->response()); });
        $this->assertSame(['checked' => 2, 'verified' => 1, 'failed' => 1], (new MembershipMonitor)->run());
        $this->assertDatabaseHas('apple_membership_checks', ['apple_membership_id' => $bad->id, 'status' => 'needs_review', 'error_code' => '21010']);
        $this->assertSame(1, $this->report()['totals']['active']);
        $this->assertSame(1, $this->report()['totals']['needs_review']);
        $this->assertSame(0, $this->report()['totals']['expired']);
        $this->assertSame(2, DB::table('apple_membership_sync_runs')->first()->checked);
    }

    public function test_sandbox_retry_is_recorded_and_excluded_from_real_membership_totals()
    {
        [, $source] = $this->apple();
        $sandbox = $this->response();
        unset($sandbox['environment']); // Older responses can omit the environment field.
        $this->fakeHttp([
            'https://buy.itunes.apple.com/*' => Http::response(['status' => 21007]),
            'https://sandbox.itunes.apple.com/*' => Http::response($sandbox),
        ]);
        (new MembershipMonitor)->run();
        Http::assertSentCount(2);
        $this->assertSame(1, $this->report()['totals']['sandbox']);
        $this->assertSame(0, $this->report()['totals']['active']);
    }

    public function test_real_trials_refunds_expiry_and_billing_grace_are_distinct()
    {
        [, $source] = $this->apple();
        $cases = [
            [$this->response([$this->transaction(['is_trial_period' => 'true'])]), 'trial'],
            [$this->response([$this->transaction(['cancellation_date_ms' => (string) (now()->subDay()->getTimestamp() * 1000)])]), 'revoked'],
            [$this->response([$this->transaction(['expires_date_ms' => (string) (now()->subDay()->getTimestamp() * 1000)])], ['status' => 21006]), 'expired'],
            [$this->response([$this->transaction(['expires_date_ms' => (string) (now()->subDay()->getTimestamp() * 1000)])], ['pending_renewal_info' => [['original_transaction_id' => 'original-1', 'product_id' => $source->plan, 'grace_period_expires_date_ms' => (string) (now()->addDay()->getTimestamp() * 1000)]]]), 'grace_period'],
        ];
        foreach ($cases as [$response, $expected]) {
            $this->fakeHttp(['*' => Http::response($response)]);
            $this->assertSame($expected, (new AdminReceiptVerifier)->verify($source)['status']);
        }
    }

    public function test_invalid_receipts_and_configuration_errors_remain_unknown()
    {
        [, $source] = $this->apple();
        foreach ([
            [$this->response([], ['receipt' => ['bundle_id' => 'another.app']]), 'bundle_mismatch'],
            [$this->response([]), 'subscription_not_found'],
            [$this->response([$this->transaction(['expires_date_ms' => 'invalid'])]), 'subscription_not_found'],
            [['status' => 21004], '21004'], [['status' => 21005], '21005'], [[], 'invalid_response'],
        ] as [$response, $code]) {
            $this->fakeHttp(['*' => Http::response($response)]);
            $this->assertSame(['error_code' => $code], (new AdminReceiptVerifier)->verify($source));
        }
        config(['apple-membership-monitor.shared_secret' => 'rotated-test-secret']);
        $this->fakeHttp(['*' => Http::response($this->response())]);
        (new AdminReceiptVerifier)->verify($source);
        Http::assertSent(function ($r) { return $r['password'] === 'rotated-test-secret'; });
    }

    public function test_retries_respect_due_times_and_preserve_last_verified_evidence()
    {
        [, $source] = $this->apple();
        $this->fakeHttp(['*' => Http::response($this->response())]);
        $monitor = new MembershipMonitor;
        $monitor->run();
        $this->assertSame(0, $monitor->run()['checked']);
        $this->travel(7)->hours();
        $this->fakeHttp(['*' => Http::response(['status' => 21010])]);
        $monitor->run();
        $check = DB::table('apple_membership_checks')->first();
        $this->assertNotNull($check->verified_at);
        $this->assertNotNull($check->expires_at);
        $this->assertSame('needs_review', $check->status);
        $this->assertSame(1, $check->failures);
        $this->assertTrue(carbon($check->next_check_at)->equalTo(now()->addHour()));
        $this->assertSame(0, $this->report()['totals']['active']);
        $this->travel(2)->hours(); $monitor->run();
        $this->assertSame(2, DB::table('apple_membership_checks')->first()->failures);
    }

    public function test_overdue_evidence_is_unknown_and_expiry_recalculates_between_checks()
    {
        [, $source] = $this->apple();
        $this->fakeHttp(['*' => Http::response($this->response([$this->transaction(['expires_date_ms' => (string) (now()->addHour()->getTimestamp() * 1000)])]))]);
        (new MembershipMonitor)->run();
        $this->assertTrue(carbon(DB::table('apple_membership_checks')->first()->next_check_at)->equalTo(now()->addHour()));
        $this->travel(2)->hours();
        $this->assertSame(1, $this->report()['totals']['expired']);
        $this->travel(2)->days();
        $this->assertSame(1, $this->report()['totals']['needs_review']);
    }

    public function test_a_receipt_changed_during_a_check_is_not_saved_as_current_evidence()
    {
        [, $source] = $this->apple();
        $this->fakeHttp(function () use ($source) {
            $source->update(['latest_receipt' => 'replaced-by-mobile']);
            return Http::response($this->response());
        });
        $this->assertSame(0, (new MembershipMonitor)->run()['checked']);
        $this->assertDatabaseCount('apple_membership_checks', 0);
        $this->assertSame(1, $this->report()['totals']['pending']);
    }

    public function test_limits_locks_test_account_exclusions_and_scheduler_registration()
    {
        $this->apple(); $this->apple();
        $this->apple([], ['id' => 284]);
        $this->fakeHttp(['*' => Http::response($this->response())]);
        $monitor = new MembershipMonitor;
        $this->assertSame(1, $monitor->run(1)['checked']);
        $lock = Cache::lock('apple-membership-monitor', 7200); $lock->get();
        $this->assertNull($monitor->run()); $lock->release();
        $this->assertSame(1, $monitor->run()['checked']);
        Http::assertSentCount(2);
        $events = app(\Illuminate\Console\Scheduling\Schedule::class)->events();
        $event = collect($events)->first(function ($event) { return strpos($event->command ?? '', 'memberships:sync-apple') !== false; });
        $this->assertSame('0 * * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
    }

    public function test_stripe_cancellations_trials_and_ended_super_users_have_correct_billing_totals()
    {
        foreach ([
            ['status' => 'active', 'renews_at' => now()->addDay()],
            ['status' => 'trialing', 'renews_at' => now()->addDays(5)],
            ['status' => 'active', 'renews_at' => now()->subDay(), 'membership_ends_at' => now()->addDays(3)],
            ['status' => 'active', 'renews_at' => now()->addMonth(), 'ended_at' => now()],
        ] as $attributes) {
            $user = Model::withoutEvents(function () { return create(User::class, ['super_user' => true]); });
            $source = Stripe::create($attributes + ['stripe_id' => 'test-'.$user->id, 'plan' => 'monthly']);
            $user->membership()->create(['source_type' => Stripe::class, 'source_id' => $source->id]);
            $this->assertTrue($user->hasActiveSubscription());
        }
        $report = $this->report();
        foreach (['active', 'trial', 'ending', 'expired'] as $status) $this->assertSame(1, $report['totals'][$status]);
    }

    public function test_directory_paginates_filters_and_renders_failure_details_safely()
    {
        [$user, $source] = $this->apple([], ['first_name' => 'Lena <script>', 'email' => 'lena@example.test']);
        $this->fakeHttp(['*' => Http::response(['status' => 21010])]); (new MembershipMonitor)->run();
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.analytics.memberships', ['provider' => 'apple', 'status' => 'needs_review', 'search' => $user->id]))->assertOk();
        $response->assertSee('Lena &lt;script&gt;', false)->assertSee('21010')->assertSee('Current membership status is unknown.')->assertDontSee('test-receipt')->assertDontSee('test-secret');
        $this->assertSame(1, $response->viewData('memberships')->total());
        for ($i = 0; $i < 26; $i++) $this->apple();
        $this->assertCount(25, $this->report()['memberships']);
        $this->assertSame(27, $this->report()['memberships']->total());
        $this->assertSame(1, $this->report(['search' => 'lena@'])['memberships']->total());
        $this->assertSame(0, $this->report(['search' => '%'])['memberships']->total());
        $this->assertSame(1, $this->report(['search' => 'Lena <script>'])['memberships']->total());
    }

    public function test_old_admin_buttons_redirect_to_analytics_without_network_or_billing_writes()
    {
        [$user, $source] = $this->apple();
        $this->actingAs(create(Admin::class), 'admin');
        $this->get(route('admin.users.index'))->assertOk()->assertDontSee('Validate all subscriptions');
        $this->get(route('admin.memberships.validate.all'))->assertRedirect(route('admin.analytics.memberships', ['provider' => 'apple']));
        $this->post(route('admin.memberships.validate.user', $user))->assertRedirect(route('admin.analytics.memberships', ['provider' => 'apple']));
        Http::assertNothingSent();
        $this->assertDatabaseCount('apple_membership_checks', 0);
        $api = app('router')->getRoutes()->getByName('api.memberships.status');
        $this->assertSame('App\\Http\\Controllers\\MembershipsController@status', $api->getActionName());
    }

    public function test_command_reports_partial_failure_and_invalid_limits_without_live_services()
    {
        $this->apple();
        $this->fakeHttp(['*' => Http::response(['status' => 21010])]);
        $this->artisan('memberships:sync-apple')->expectsOutput('Checked 1; verified 0; needs review 1.')->assertExitCode(1);
        $this->artisan('memberships:sync-apple', ['--limit' => '0'])->assertExitCode(1);
        config(['apple-membership-monitor.enabled' => false]);
        $this->artisan('memberships:sync-apple')->assertExitCode(0);
        Http::assertSentCount(1);
    }

    public function test_report_handles_migration_not_yet_deployed_and_requires_admin_session()
    {
        $this->apple();
        Schema::drop('apple_membership_checks'); Schema::drop('apple_membership_sync_runs');
        $this->withExceptionHandling()->get(route('admin.analytics.memberships'))->assertRedirect(route('admin.login.show'));
        $this->actingAs(create(Admin::class), 'admin');
        $this->get(route('admin.analytics.memberships'))->assertOk()->assertSee('Setup required')->assertSee('Not checked');
    }

    public function test_reporting_queries_stay_bounded_as_memberships_grow_and_reject_invalid_filters()
    {
        [$user, $source] = $this->apple();
        // Duplicate billing records must not count a second membership for this account.
        $user->membership()->create(['source_type' => Apple::class, 'source_id' => $source->id]);
        DB::enableQueryLog(); DB::flushQueryLog();
        $this->assertSame(1, $this->report()['memberships']->total());
        $small = count(DB::getQueryLog());
        DB::disableQueryLog();
        for ($i = 0; $i < 30; $i++) $this->apple();
        DB::enableQueryLog(); DB::flushQueryLog();
        $this->assertSame(31, $this->report()['memberships']->total());
        $this->assertSame($small, count(DB::getQueryLog()));
        $this->assertLessThanOrEqual(10, $small); // Includes independent super-user totals/directory.
        DB::disableQueryLog();
        $this->actingAs(create(Admin::class), 'admin')->withExceptionHandling();
        $this->getJson(route('admin.analytics.memberships', ['sort' => 'password']))->assertStatus(422);
        $this->getJson(route('admin.analytics.memberships', ['provider' => ['apple']]))->assertStatus(422);
    }

    public function test_disabled_renewal_preserves_trial_access_until_expiry_and_ignores_old_grace_dates()
    {
        [, $source] = $this->apple();
        $this->fakeHttp(['*' => Http::response($this->response([$this->transaction(['is_trial_period' => 'true'])], [
            'pending_renewal_info' => [['original_transaction_id' => 'original-1', 'product_id' => $source->plan, 'auto_renew_status' => '0', 'grace_period_expires_date_ms' => (string) (now()->subDay()->getTimestamp() * 1000)]],
        ]))]);
        (new MembershipMonitor)->run();
        $this->assertSame(1, $this->report()['totals']['ending']);
        $this->assertTrue(carbon($this->report()['memberships']->first()->access_until)->equalTo(now()->addMonth()));
        $this->assertNull(DB::table('apple_membership_checks')->first()->grace_expires_at);
        $this->travel(1)->seconds();
        $source->update(['latest_receipt' => 'updated-receipt']);
        $this->assertSame(1, $this->report()['totals']['pending']);
    }

    public function test_overview_active_memberships_match_analytics_and_keep_the_existing_card_layout()
    {
        foreach (['active', 'grace_period', 'trial', 'expired', 'revoked', 'needs_review', 'pending', 'sandbox'] as $status) {
            [$user, $source] = $this->apple();
            if ($status === 'pending') continue;
            DB::table('apple_membership_checks')->insert([
                'apple_membership_id' => $source->id, 'status' => $status === 'sandbox' ? 'active' : $status,
                'environment' => $status === 'sandbox' ? 'Sandbox' : 'Production',
                'expires_at' => in_array($status, ['expired', 'grace_period']) ? now()->subDay() : now()->addMonth(),
                'grace_expires_at' => $status === 'grace_period' ? now()->addDay() : null,
                'checked_at' => now(), 'source_updated_at' => $source->updated_at,
            ]);
            if ($status === 'active') $user->membership()->update(['created_at' => now()->subMonth()]);
        }
        foreach (['active', 'trialing', 'canceled'] as $status) {
            $user = Model::withoutEvents(function () { return create(User::class); });
            $source = Stripe::create(['stripe_id' => 'overview-'.$user->id, 'plan' => 'monthly', 'status' => $status, 'renews_at' => now()->addMonth()]);
            $user->membership()->create(['source_type' => Stripe::class, 'source_id' => $source->id]);
        }
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.home'))->assertOk()->assertDontSee('Published posts');
        $metric = $response->viewData('dashboard')['metrics'][1];
        $this->assertSame('Active memberships', $metric['label']);
        $this->assertSame(3, $metric['total']);
        $this->assertSame(2, $metric['added']);
        $totals = $this->report()['totals'];
        $this->assertSame($totals['active'] + $totals['grace_period'], $metric['total']);
        $document = new \DOMDocument; @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $cards = $xpath->query('//div[@class="admin-metrics-grid"]/a');
        $this->assertSame(4, $cards->length);
        $this->assertStringContainsString('Total users', $cards[0]->textContent);
        $this->assertStringContainsString('Active memberships', $cards[1]->textContent);
        $this->assertSame(route('admin.analytics.memberships'), $cards[1]->getAttribute('href'));
        $this->assertSame(1, $xpath->query('.//div[@class="admin-metric-progress"]', $cards[1])->length);
    }
}
