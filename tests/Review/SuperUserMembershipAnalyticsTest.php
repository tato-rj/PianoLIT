<?php

namespace Tests\Review;

use App\{Admin, User};
use App\Analytics\SuperUserReport;
use App\Mail\SuperUserEmail;
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

    public function test_all_current_super_users_are_listed_without_turning_them_into_paid_memberships()
    {
        $first = $this->account(['super_user' => true, 'first_name' => 'Lena <script>', 'last_name' => 'Rivera', 'email' => 'lena@example.test', 'created_at' => now()->subYears(2)]);
        $second = $this->account(['super_user' => true]);
        $this->account(['super_user' => false]);
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.analytics.memberships'))->assertOk();
        $this->assertSame(2, $response->viewData('superUserTotal'));
        $this->assertSame(0, $response->viewData('superUserDated'));
        $this->assertSame(0, $response->viewData('totals')['active']);
        $this->assertSame(0, $response->viewData('memberships')->total());
        $response->assertSee('Lena &lt;script&gt;', false)->assertSee('Full access without payment')->assertSee('Not recorded')->assertSee('Oct 13, 2024');
        foreach ($response->viewData('superUsers') as $account) $this->assertNull($account->granted_at);
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
        $this->get(route('admin.analytics.memberships'))->assertOk()->assertSee('Already enabled on Oct 13, 2026')->assertSee('Not recorded');
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
        Mail::assertSent(SuperUserEmail::class, 1);
        $this->get(route('admin.analytics.memberships'))->assertOk()->assertSee('Access Manager')->assertSee('Oct 13, 2026');
        $this->travel(2)->days();
        $this->patchJson(route('admin.users.super-status', $user))->assertOk();
        $this->assertFalse($user->fresh()->hasActiveSubscription());
        $first = DB::table('super_user_access_periods')->where('id', $first->id)->first();
        $this->assertSame(now()->toDateTimeString(), $first->revoked_at);
        $this->assertSame($admin->id, $first->revoked_by);
        $this->assertNull($first->current_user_id);
        $this->travel(1)->days();
        $this->patchJson(route('admin.users.super-status', $user))->assertOk();
        $this->assertDatabaseCount('super_user_access_periods', 2);
        $period = DB::table('super_user_access_periods')->where('current_user_id', $user->id)->sole();
        $this->assertSame(now()->toDateTimeString(), $period->granted_at);
        $this->assertSame(1, (new SuperUserReport)->get(Request::create('/'))['superUserTotal']);
        Mail::assertSent(SuperUserEmail::class, 2);
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

    public function test_super_user_search_pagination_and_counts_are_independent_from_membership_filters()
    {
        $user = $this->account(['super_user' => true, 'first_name' => 'Lena', 'last_name' => 'Rivera', 'email' => 'lena@example.test']);
        for ($i = 0; $i < 16; $i++) $this->account(['super_user' => true]);
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.analytics.memberships', ['provider' => 'apple', 'status' => 'expired', 'super_search' => 'Lena Rivera']))->assertOk();
        $this->assertSame(17, $response->viewData('superUserTotal'));
        $this->assertSame(1, $response->viewData('superUsers')->total());
        $this->assertSame($user->id, $response->viewData('superUsers')->first()->id);
        $response = $this->get(route('admin.analytics.memberships', ['super_page' => 2]))->assertOk();
        $this->assertCount(2, $response->viewData('superUsers'));
        $this->assertSame(0, (new SuperUserReport)->get(Request::create('/', 'GET', ['super_search' => '%']))['superUsers']->total());
        $this->withExceptionHandling()->getJson(route('admin.analytics.memberships', ['super_sort' => 'password']))->assertStatus(422);
        $this->getJson(route('admin.analytics.memberships', ['super_search' => ['bad']]))->assertStatus(422);
    }

    public function test_reporting_and_the_existing_toggle_remain_usable_before_the_tracking_migration()
    {
        Schema::drop('super_user_access_periods');
        $user = $this->account(['super_user' => true]);
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.analytics.memberships'))->assertOk()->assertSee('Grant-date tracking needs server setup.');
        $this->assertSame(1, $response->viewData('superUserTotal'));
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
