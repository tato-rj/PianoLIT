<?php

namespace Tests\Review;

use App\{Admin, User};
use App\Analytics\MembershipReport;
use App\Billing\Sources\{Apple, Stripe};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Http, Mail, Schema};

class SuperUserMembershipAnalyticsTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->travelTo(carbon('2026-10-13 15:00:00'));
    }

    private function account(array $attributes = [])
    {
        return Model::withoutEvents(function () use ($attributes) { return create(User::class, $attributes); });
    }

    private function report(array $filters = [])
    {
        return (new MembershipReport)->get(Request::create('/analytics/memberships', 'GET', $filters));
    }

    private function rowCells($response, $userId)
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $cells = (new \DOMXPath($dom))->query('//section[@aria-labelledby="ma-directory-title"]//tbody/tr[td/a[@href="'.route('admin.users.show', $userId).'"]]/td');
        $this->assertCount(5, $cells);
        return array_map(function ($cell) {
            $text = '';
            foreach ($cell->childNodes as $child) $text .= ' '.$child->textContent;
            return trim(preg_replace('/\s+/u', ' ', $text));
        }, iterator_to_array($cells));
    }

    public function test_all_current_super_users_are_listed_without_turning_them_into_paid_memberships()
    {
        $first = $this->account(['super_user' => true, 'first_name' => 'Lena <script>', 'last_name' => 'Rivera', 'email' => 'lena@example.test', 'created_at' => now()->subYears(2)]);
        $second = $this->account(['super_user' => true]);
        $this->account(['super_user' => false]);
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.analytics.memberships'))->assertOk();
        $this->assertSame(2, $response->viewData('counts')['super_user']['active']);
        $this->assertSame(0, $response->viewData('totals')['active']);
        $this->assertSame(2, $response->viewData('memberships')->total());
        $response->assertSee('Lena &lt;script&gt;', false)->assertSee('Super User')->assertDontSee('Full access without payment')->assertDontSee('Oct 13, 2024');
        foreach ($response->viewData('memberships') as $account) {
            $this->assertSame('super_user', $account->provider);
            $this->assertSame('active', $account->membership_status);
            $this->assertNull($account->access_until);
            $this->assertNull($account->checked_at);
            $this->assertSame(['Super User', 'Active', '', ''], array_slice($this->rowCells($response, $account->user_id), 1));
        }
        $this->assertTrue($first->hasActiveSubscription());
        $this->assertTrue($second->hasActiveSubscription());
        Http::assertNothingSent(); Mail::assertNothingSent();
    }

    public function test_migration_observes_existing_access_without_fabricating_grant_dates()
    {
        Schema::drop('super_user_access_periods');
        $user = $this->account(['super_user' => true, 'created_at' => now()->subYears(3)]);
        $this->account(['super_user' => false]);
        require_once database_path('migrations/2026_10_13_190000_create_super_user_access_periods_table.php');
        (new \CreateSuperUserAccessPeriodsTable)->up();
        $period = DB::table('super_user_access_periods')->sole();
        $this->assertSame($user->id, $period->current_user_id);
        $this->assertNull($period->granted_at);
        $this->assertNull($period->granted_by);
        $this->assertSame(now()->toDateTimeString(), $period->first_observed_at);
        $this->assertTrue($user->fresh()->super_user);
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.analytics.memberships'))->assertOk();
        $this->assertNull($response->viewData('memberships')->first()->checked_at);
        $this->assertSame('', $this->rowCells($response, $user->id)[4]);
    }

    public function test_admin_toggle_records_each_current_grant_and_revocation_without_changing_its_response()
    {
        $user = $this->account(['super_user' => false]);
        $other = $this->account(['super_user' => false]);
        $admin = create(Admin::class, ['name' => 'Access Manager']);
        $this->actingAs($admin, 'admin');
        $this->patchJson(route('admin.users.super-status', $user), ['user_id' => $other->id])->assertOk()->assertExactJson(['status' => $user->fullName."'s super status has been updated."]);
        $this->assertTrue($user->fresh()->hasActiveSubscription());
        $this->assertFalse($other->fresh()->super_user);
        $first = DB::table('super_user_access_periods')->sole();
        $this->assertSame(now()->toDateTimeString(), $first->granted_at);
        $this->assertSame($admin->id, $first->granted_by);
        $this->assertNull($first->revoked_at);
        Mail::assertNothingOutgoing();
        $response = $this->get(route('admin.analytics.memberships'))->assertOk();
        $this->assertSame(['Super User', 'Active', '', 'Oct 13, 2026 3:00 PM'], array_slice($this->rowCells($response, $user->id), 1));
        $this->travel(2)->days();
        $this->patchJson(route('admin.users.super-status', $user))->assertOk();
        $this->assertFalse($user->fresh()->hasActiveSubscription());
        Mail::assertNothingOutgoing();
        $first = DB::table('super_user_access_periods')->where('id', $first->id)->first();
        $this->assertSame(now()->toDateTimeString(), $first->revoked_at);
        $this->assertSame($admin->id, $first->revoked_by);
        $this->assertNull($first->current_user_id);
        $this->travel(1)->days();
        $this->patchJson(route('admin.users.super-status', $user))->assertOk();
        $this->assertDatabaseCount('super_user_access_periods', 2);
        $period = DB::table('super_user_access_periods')->where('current_user_id', $user->id)->sole();
        $this->assertSame(now()->toDateTimeString(), $period->granted_at);
        $report = $this->report();
        $this->assertSame(1, $report['counts']['super_user']['active']);
        $this->assertSame(now()->toDateTimeString(), $report['memberships']->first()->checked_at);
        Mail::assertNothingOutgoing();
        Http::assertNothingSent();
        $this->assertArrayNotHasKey('granted_at', $user->fresh()->getAttributes());
    }

    public function test_a_failed_audit_write_rolls_back_access_and_does_not_send_grant_mail()
    {
        $user = $this->account(['super_user' => false]);
        $this->actingAs(create(Admin::class), 'admin');
        DB::statement("CREATE TRIGGER fail_grant BEFORE INSERT ON super_user_access_periods BEGIN SELECT RAISE(ABORT, 'audit unavailable'); END");
        try {
            $this->patchJson(route('admin.users.super-status', $user));
            $this->fail('The audit failure must propagate.');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertStringContainsString('audit unavailable', $e->getMessage());
        }
        $this->assertFalse($user->fresh()->super_user);
        $this->assertDatabaseCount('super_user_access_periods', 0);
        Mail::assertNothingSent();
    }

    public function test_super_users_share_directory_filters_search_and_pagination()
    {
        $user = $this->account(['super_user' => true, 'first_name' => 'Lena', 'last_name' => 'Rivera', 'email' => 'lena@example.test']);
        for ($i = 0; $i < 16; $i++) $this->account(['super_user' => true]);
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.analytics.memberships', ['provider' => 'super_user', 'status' => 'active', 'search' => 'Lena Rivera']))->assertOk();
        $this->assertSame(17, $response->viewData('counts')['super_user']['active']);
        $this->assertSame(1, $response->viewData('memberships')->total());
        $this->assertSame($user->id, $response->viewData('memberships')->first()->user_id);
        $this->assertSame(1, $this->report(['search' => 'lena@'])['memberships']->total());
        $this->assertContains($user->id, $this->report(['search' => (string) $user->id])['memberships']->pluck('user_id'));
        $firstPage = $this->get(route('admin.analytics.memberships', ['provider' => 'super_user', 'status' => 'active', 'sort' => 'name']))->assertOk()->viewData('memberships');
        $this->assertSame(12, $firstPage->perPage());
        $this->assertCount(12, $firstPage);
        $this->assertStringContainsString('provider=super_user', $firstPage->nextPageUrl());
        $this->assertStringContainsString('status=active', $firstPage->nextPageUrl());
        $this->assertStringContainsString('sort=name', $firstPage->nextPageUrl());
        $response = $this->get(route('admin.analytics.memberships', ['provider' => 'super_user', 'status' => 'active', 'sort' => 'name', 'page' => 2]))->assertOk();
        $this->assertCount(5, $response->viewData('memberships'));
        $this->assertCount(0, $firstPage->pluck('user_id')->intersect($response->viewData('memberships')->pluck('user_id')));
        foreach (['apple', 'stripe', 'billing'] as $provider) $this->assertSame(0, $this->report(['provider' => $provider])['memberships']->total());
        foreach (['expired', 'trial', 'needs_review'] as $status) $this->assertSame(0, $this->report(['provider' => 'super_user', 'status' => $status])['memberships']->total());
        $this->assertSame(0, $this->report(['search' => '%'])['memberships']->total());
        $this->withExceptionHandling()->getJson(route('admin.analytics.memberships', ['provider' => 'password']))->assertStatus(422);
        $this->getJson(route('admin.analytics.memberships', ['search' => ['bad']]))->assertStatus(422);
    }

    public function test_mixed_directory_has_one_row_per_account_and_keeps_paid_totals_separate()
    {
        $super = $this->account(['super_user' => true]);
        $stripe = $this->account();
        foreach ([$super, $stripe] as $user) {
            $source = Stripe::create(['stripe_id' => 'sub-test-'.$user->id, 'plan' => 'monthly', 'status' => 'active', 'renews_at' => now()->addMonth()]);
            $user->membership()->create(['source_type' => Stripe::class, 'source_id' => $source->id]);
        }
        $apple = $this->account();
        $source = Apple::create(['plan' => 'monthly', 'latest_receipt' => 'test-receipt', 'password' => 'test-secret']);
        $apple->membership()->create(['source_type' => Apple::class, 'source_id' => $source->id]);
        $report = $this->report();
        $this->assertSame(3, $report['memberships']->total());
        $this->assertSame(3, $report['memberships']->pluck('user_id')->unique()->count());
        $this->assertSame('super_user', $report['memberships']->firstWhere('user_id', $super->id)->provider);
        $this->assertSame(2, $report['totals']['active']);
        $this->assertSame(2, $report['counts']['stripe']['active']);
        $this->assertSame(1, $report['counts']['super_user']['active']);
        $this->assertSame(2, (int) (new MembershipReport)->activeTotals(now()->subDay(), now())->total);
        $this->assertSame([$stripe->id], $this->report(['provider' => 'stripe'])['memberships']->pluck('user_id')->all());
        $this->assertSame([$apple->id], $this->report(['provider' => 'apple'])['memberships']->pluck('user_id')->all());
        $this->assertEqualsCanonicalizing([$stripe->id, $apple->id], $this->report(['provider' => 'billing'])['memberships']->pluck('user_id')->all());
        $this->assertSame([$super->id], $this->report(['provider' => 'super_user'])['memberships']->pluck('user_id')->all());
        $filtered = $this->report(['provider' => 'billing', 'search' => 'missing']);
        $this->assertSame(0, $filtered['memberships']->total());
        $this->assertSame($report['counts'], $filtered['counts']);
        Http::assertNothingSent(); Mail::assertNothingSent();
    }

    public function test_latest_check_sort_uses_current_super_user_grants_and_keeps_unknown_dates_last()
    {
        $unknown = $this->account(['super_user' => true]);
        $older = $this->account(['super_user' => true]);
        $newer = $this->account(['super_user' => true]);
        foreach ([$older->id => now()->subDays(2), $newer->id => now()->subDay()] as $id => $grant) {
            DB::table('super_user_access_periods')->insert(['user_id' => $id, 'current_user_id' => $id, 'granted_at' => $grant, 'first_observed_at' => now()]);
        }
        $this->assertSame([$newer->id, $older->id, $unknown->id], $this->report(['provider' => 'super_user', 'sort' => 'checked'])['memberships']->pluck('user_id')->all());
    }

    public function test_reporting_and_the_existing_toggle_remain_usable_before_the_tracking_migration()
    {
        Schema::drop('super_user_access_periods');
        $user = $this->account(['super_user' => true]);
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.analytics.memberships'))->assertOk();
        $this->assertSame(1, $response->viewData('counts')['super_user']['active']);
        $this->assertSame(['Super User', 'Active', '', ''], array_slice($this->rowCells($response, $user->id), 1));
        $this->patchJson(route('admin.users.super-status', $user))->assertOk();
        $this->assertFalse($user->fresh()->super_user);
        Mail::assertNothingSent();
    }

    public function test_admin_session_is_required_and_mobile_billing_actions_keep_their_original_handlers()
    {
        $user = $this->account(['super_user' => false]);
        $this->withExceptionHandling()->patchJson(route('admin.users.super-status', $user))->assertUnauthorized();
        $this->assertFalse($user->fresh()->super_user);
        foreach (['status', 'store', 'history'] as $action) {
            $route = app('router')->getRoutes()->getByName('api.memberships.'.$action);
            $this->assertSame('App\\Http\\Controllers\\MembershipsController@'.$action, $route->getActionName());
        }
    }
}
