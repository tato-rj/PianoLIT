<?php

namespace Tests\Review;

use App\{Admin, EmailLog, Piece, User};
use App\Activity\{ExcludedAccounts, ExcludedHistory, Filters, Queries};
use App\Analytics\MembershipReport;
use App\Billing\Sources\Stripe;
use App\Http\Middleware\Logs\RecordWebLog;
use App\Services\{RecentlyViewedPieces, CampaignAttribution};
use App\Services\Admin\{Dashboard, UserCountries};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Redis, Route};
use Illuminate\Support\Str;

class ExcludedActivityTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->travelTo(carbon('2026-10-13 12:00:00'));
        config(['activity.minimum_visit_seconds' => 0, 'activity.filter_bots' => false]);
        Route::middleware(['web', RecordWebLog::class])->group(function () {
            Route::get('/review/private-page', function () { return response('<html>'.view('components.activity')->render().'</html>'); })->name('review.private-page');
            Route::post('/review/private-login/{user}', function (User $user) { auth('web')->login($user); return response()->noContent(); })->name('review.private-login');
            Route::post('/review/private-logout', function () { auth('web')->logout(); request()->session()->invalidate(); return response()->noContent(); })->name('review.private-logout');
        });
    }

    private function user($email = 'regular@example.test', $name = 'Regular')
    {
        return Model::withoutEvents(function () use ($email, $name) { return create(User::class, ['email' => $email, 'first_name' => $name, 'super_user' => true]); });
    }

    private function visit($user = null, $visitor = null)
    {
        $id = (string) Str::uuid();
        DB::table('activity_visits')->insert(['id' => $id, 'user_id' => optional($user)->id, 'visitor_id' => $visitor ?: (string) Str::uuid(), 'platform' => 'webapp', 'started_at' => '2026-10-13 09:00:00', 'last_activity_at' => '2026-10-13 09:10:00']);
        return $id;
    }

    private function event($user, $visit = null, $page = null, $type = 'page_view')
    {
        DB::table('activity_events')->insert(['event_id' => (string) Str::uuid(), 'user_id' => optional($user)->id, 'visit_id' => $visit, 'page_id' => $page,
            'platform' => 'webapp', 'source' => 'server', 'type' => $type, 'occurred_at' => '2026-10-13 09:01:00', 'received_at' => now(),
            'resource_type' => 'piece', 'resource_id' => '1178', 'metadata' => '{}']);
    }

    public function test_both_accounts_are_not_tracked_even_with_bot_filtering_disabled_and_mobile_json_is_preserved()
    {
        foreach (['arthurvilar@gmail.com', ' Elena.Dare@Gmail.Com '] as $email) {
            $user = $this->user($email);
            $this->actingAs($user, 'web');
            $this->get('/review/private-page')->assertOk()->assertDontSee('pianoActivityConfig');
            $this->postJson('/activity/events', ['events' => []])->assertNoContent();
            $this->getJson(route('api.users.favorites.show', ['user_id' => $user->id]))->assertOk()->assertExactJson([]);
            $viewResponse = $this->postJson(route('api.pieces.increment-views'), ['user_id' => $user->id, 'piece_id' => 999999])->assertOk();
            $this->assertSame("The piece's views have been incremented", $viewResponse->json());
            $piece = Model::withoutEvents(function () { return create(Piece::class); });
            (new RecentlyViewedPieces)->record($user, $piece);
            $this->assertCount(0, (new RecentlyViewedPieces)->forUser($user));
            $this->assertTrue($piece->hasWebMediaAccess($user));
            $this->assertTrue($user->fresh()->super_user);
            $this->assertSame(0, $user->fresh()->logs_count);
            $this->assertNull($user->fresh()->last_active_at);
        }
        foreach (['activity_events', 'activity_pages', 'activity_visits', 'piece_views', 'recently_viewed_pieces'] as $table) $this->assertDatabaseCount($table, 0);
    }

    public function test_known_browser_stays_excluded_after_logout_but_another_account_resumes_tracking()
    {
        $excluded = $this->user('arthurvilar@gmail.com'); $regular = $this->user();
        $this->actingAs($excluded, 'web');
        $this->get('/review/private-page')->assertOk()->assertCookie(ExcludedAccounts::COOKIE);
        $this->post('/review/private-logout')->assertNoContent();
        $this->withCookie(ExcludedAccounts::COOKIE, '1')->get('/review/private-page')->assertOk()->assertDontSee('pianoActivityConfig');
        $this->assertDatabaseCount('activity_events', 0);
        $this->post('/review/private-login/'.$regular->id)->assertNoContent();
        $this->get('/review/private-page?user_id='.$excluded->id)->assertOk()->assertSee('pianoActivityConfig');
        $this->assertDatabaseHas('activity_pages', ['user_id' => $regular->id]);
        $this->assertDatabaseMissing('activity_events', ['user_id' => $excluded->id]);
        $this->assertDatabaseCount('activity_visits', 1);
    }

    public function test_verified_login_removes_prior_anonymous_context_without_deleting_another_accounts_linked_visit()
    {
        $excluded = $this->user('elena.dare@gmail.com'); $regular = $this->user();
        $this->get('/review/private-page')->assertOk();
        $anonymous = DB::table('activity_visits')->first();
        $other = $this->visit(null, $anonymous->visitor_id);
        DB::table('activity_visit_links')->insert(['visit_id' => $other, 'user_id' => $regular->id, 'linked_at_utc' => now(), 'reason' => 'login']);
        $this->event(null, $other);
        $this->post('/review/private-login/'.$excluded->id)->assertNoContent();
        $this->assertDatabaseMissing('activity_visits', ['id' => $anonymous->id]);
        $this->assertDatabaseHas('activity_visits', ['id' => $other]);
        $this->assertDatabaseHas('activity_events', ['visit_id' => $other]);
        $this->assertDatabaseMissing('activity_visit_links', ['user_id' => $excluded->id]);
        $this->assertDatabaseMissing('activity_events', ['user_id' => $excluded->id]);
    }

    public function test_existing_direct_linked_and_ungrouped_history_is_hidden_in_reports_before_cleanup()
    {
        $arthur = $this->user('arthurvilar@gmail.com', 'Arthur'); $elena = $this->user('elena.dare@gmail.com', 'Elena'); $regular = $this->user();
        foreach ([$arthur, $elena, $regular] as $user) {
            $visit = $this->visit($user); $this->event($user, $visit); $this->event($user);
            DB::table('locations')->insert(['user_id' => $user->id, 'countryCode' => 'US']);
            $source = Stripe::create(['stripe_id' => 'cus_'.$user->id, 'status' => 'active', 'plan' => 'monthly', 'renews_at' => now()->addMonth()]);
            $user->membership()->create(['source_type' => Stripe::class, 'source_id' => $source->id]);
        }
        $linked = $this->visit();
        DB::table('activity_visit_links')->insert(['visit_id' => $linked, 'user_id' => $arthur->id, 'linked_at_utc' => now(), 'reason' => 'login']);
        $this->event(null, $linked);
        $filters = new Filters(Request::create('/', 'GET', ['range' => 'today', 'audience' => 'all']));
        $queries = new Queries;
        $this->assertSame(1, $queries->metrics($filters)['Visits']);
        $this->assertSame(1, $queries->ungrouped($filters)->count());
        $this->assertSame(1, (new UserCountries)->data()['total']);
        $this->actingAs(create(Admin::class), 'admin');
        $dashboard = (new Dashboard)->data(7, 'all');
        $this->assertSame(2, $dashboard['total']);
        $this->assertSame([$regular->id], $dashboard['engagedUsers']->pluck('id')->all());
        $this->assertSame(1, $dashboard['metrics'][0]['total']);
        $members = (new MembershipReport)->get(Request::create('/'));
        $this->assertSame(1, $members['memberships']->total());
        $this->assertSame(1, $members['superUsers']->total());
        $this->get(route('admin.analytics.users.people'))->assertOk()->assertDontSee('Arthur')->assertDontSee('Elena');
        $this->getJson(route('admin.users.logs'), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertJsonPath('recordsTotal', 1);
        $this->withExceptionHandling()->get(route('admin.analytics.users.journey', $linked))->assertNotFound();
        $this->get(route('admin.analytics.users.profile', $arthur))->assertNotFound();
        $this->get(route('admin.users.load-logs', ['user' => $arthur->id, 'type' => 'webapp']))->assertOk()->assertDontSee('Open timeline');
    }

    public function test_purge_removes_only_owned_tracking_and_rebuilds_affected_email_summaries()
    {
        $excluded = $this->user('arthurvilar@gmail.com'); $regular = $this->user();
        $piece = Model::withoutEvents(function () { return create(Piece::class); });
        $private = $this->visit($excluded); $other = $this->visit($regular);
        foreach ([$private => $excluded, $other => $regular] as $visit => $user) {
            $page = (string) Str::uuid();
            DB::table('activity_pages')->insert(['id' => $page, 'visit_id' => $visit, 'user_id' => $user->id, 'visitor_id' => (string) Str::uuid(), 'platform' => 'webapp', 'opened_at' => now(), 'url' => '/piece']);
            $this->event($user, $visit, $page); $this->event($user);
            DB::table('piece_views')->insert(['user_id' => $user->id, 'piece_id' => $piece->id]);
            DB::table('recently_viewed_pieces')->insert(['user_id' => $user->id, 'piece_id' => $piece->id, 'viewed_at' => now()]);
            DB::table('locations')->insert(['user_id' => $user->id, 'countryCode' => 'US']);
            DB::table('logs')->insert(['user_id' => $user->id, 'total' => 2]);
            DB::table('email_logs')->insert(['message_id' => 'message-'.$user->id, 'list_id' => 'list-privacy', 'sender_type' => 'App\\EmailList', 'sender_id' => 1, 'recipient' => $user->email, 'unique_opened' => 1]);
            DB::table('campaign_visits')->insert(['id' => $visit, 'campaign' => 'test', 'user_id' => $user->id, 'consented_at' => now(), 'expires_at' => now()->addMonth(), 'created_at' => now()]);
            DB::table('campaign_subscriptions')->insert(['visit_id' => $visit, 'stripe_subscription_id' => 'sub_'.$user->id, 'stripe_customer_id' => 'cus_'.$user->id, 'billing_account' => 'us', 'plan' => 'monthly', 'subscribed_at' => now()]);
            $source = \App\Billing\Sources\Apple::create(['plan' => 'monthly', 'renews_at' => now()->addMonth(), 'latest_receipt' => 'synthetic-receipt', 'password' => 'synthetic-secret']);
            $user->membership()->create(['source_type' => \App\Billing\Sources\Apple::class, 'source_id' => $source->id]);
            DB::table('apple_membership_checks')->insert(['apple_membership_id' => $source->id, 'status' => 'active']);
        }
        DB::table('email_campaign_reports')->insert(['list_id' => 'list-privacy', 'name' => 'Shared newsletter', 'sent_at' => now(), 'emails_count' => 100, 'opens_count' => 100]);
        DB::table('favorites')->insert(['user_id' => $excluded->id, 'piece_id' => $piece->id]);
        DB::table('score_annotations')->insert(['user_id' => $excluded->id, 'piece_id' => $piece->id, 'score_key' => str_repeat('a', 64), 'marks' => '[]']);
        $history = new ExcludedHistory; $before = $history->counts();
        $this->artisan('activity:purge-excluded')->assertExitCode(0);
        $this->assertSame($before, $history->counts());
        $this->artisan('activity:purge-excluded', ['--execute' => true])->assertExitCode(0);
        $remaining = $history->counts(); unset($remaining['accounts']); $this->assertSame(0, array_sum($remaining));
        $this->assertDatabaseCount('activity_events', 2);
        $this->assertDatabaseHas('activity_visits', ['id' => $other]);
        $this->assertDatabaseHas('locations', ['user_id' => $regular->id]);
        $this->assertDatabaseHas('email_logs', ['recipient' => $regular->email]);
        $this->assertDatabaseHas('email_campaign_reports', ['list_id' => 'list-privacy', 'emails_count' => 1, 'opens_count' => 1]);
        $this->assertDatabaseHas('favorites', ['user_id' => $excluded->id]);
        $this->assertDatabaseHas('score_annotations', ['user_id' => $excluded->id]);
        $this->assertDatabaseHas('users', ['id' => $excluded->id, 'super_user' => true]);
        $this->assertDatabaseCount('memberships', 2);
        $this->assertDatabaseCount('apple_memberships', 2);
        $this->assertDatabaseCount('apple_membership_checks', 1);
        $this->assertDatabaseHas('campaign_subscriptions', ['visit_id' => $other]);
        $this->assertSame([$regular->email], (new \App\Exports\Subscriptions\Factories\Members)->generate());
        $this->artisan('activity:purge-excluded', ['--execute' => true])->assertExitCode(0);
        $this->assertDatabaseCount('activity_events', 2);
        $log = EmailLog::create(['message_id' => 'excluded-new', 'sender_type' => 'App\\EmailList', 'sender_id' => 1, 'recipient' => $excluded->email]);
        $this->assertFalse($log->exists);
    }

    public function test_a_recognized_account_removes_its_anonymous_campaign_without_an_activity_session()
    {
        $excluded = $this->user('elena.dare@gmail.com'); $regular = $this->user();
        $anonymous = (string) Str::uuid(); $owned = (string) Str::uuid();
        foreach ([$anonymous => null, $owned => $regular->id] as $id => $user) {
            DB::table('campaign_visits')->insert(['id' => $id, 'campaign' => 'test', 'user_id' => $user, 'consented_at' => now(), 'expires_at' => now()->addMonth(), 'created_at' => now()]);
        }
        $this->actingAs($excluded, 'web');
        $request = Request::create('https://my.pianolit.com/', 'GET', [], ['pianolit_campaign_visit' => $anonymous]);
        ExcludedAccounts::synchronize($request);
        $this->assertDatabaseMissing('campaign_visits', ['id' => $anonymous]);
        $this->assertFalse(traffic()->isRealVisitor());
        (new ExcludedHistory)->anonymous(null, $owned);
        $this->assertDatabaseHas('campaign_visits', ['id' => $owned]);
    }

    public function test_a_failed_purge_batch_rolls_back_visits_pages_and_events()
    {
        $excluded = $this->user('elena.dare@gmail.com'); $visit = $this->visit($excluded); $page = (string) Str::uuid();
        DB::table('activity_pages')->insert(['id' => $page, 'visit_id' => $visit, 'user_id' => $excluded->id, 'visitor_id' => (string) Str::uuid(), 'platform' => 'webapp', 'opened_at' => now(), 'url' => '/piece']);
        $this->event($excluded, $visit, $page);
        DB::statement("CREATE TRIGGER excluded_purge_fail BEFORE DELETE ON activity_pages BEGIN SELECT RAISE(ABORT, 'simulated failure'); END");
        try { (new ExcludedHistory)->purge(); $this->fail('Cleanup must roll back on a database failure.'); }
        catch (\Illuminate\Database\QueryException $exception) {
            $this->assertDatabaseCount('activity_visits', 1); $this->assertDatabaseCount('activity_pages', 1); $this->assertDatabaseCount('activity_events', 1);
        }
    }

    public function test_optional_redis_cleanup_deletes_only_exact_owned_activity_hashes()
    {
        $excluded = $this->user('arthurvilar@gmail.com'); $this->user();
        $key = 'user:'.$excluded->id.':app'; $redis = \Mockery::mock();
        Redis::shouldReceive('connection')->with('default')->once()->andReturn($redis);
        $redis->shouldReceive('exists')->andReturnUsing(function ($candidate) use ($key) { return $candidate === $key; });
        $redis->shouldReceive('type')->with($key)->once()->andReturn('hash');
        $redis->shouldReceive('del')->with($key)->once();
        $this->artisan('activity:purge-excluded', ['--execute' => true, '--legacy-redis' => true])->assertExitCode(0);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_production_tracker_templates_are_omitted_for_excluded_accounts()
    {
        $environment = app()->environment();
        app()->instance('env', 'production');
        try {
            view()->share('errors', new \Illuminate\Support\ViewErrorBag);
            $this->actingAs($this->user('elena.dare@gmail.com'), 'web');
            foreach (['manager-head', 'manager-body'] as $partial) {
                $this->assertStringNotContainsString('googletagmanager.com', view('layouts.html.google.'.$partial)->render());
            }
            $this->assertStringNotContainsString('connect.facebook.net', view('auth.webapp.login', ['errors' => new \Illuminate\Support\ViewErrorBag])->render());
            $this->actingAs($this->user(), 'web');
            $this->assertStringContainsString('googletagmanager.com', view('layouts.html.google.manager-head')->render());
            auth('web')->logout();
            $this->actingAs(create(Admin::class, ['email' => 'arthurvilar@gmail.com']), 'admin');
            $request = Request::create('https://admin.pianolit.com/');
            ExcludedAccounts::synchronize($request);
            $this->assertSame('1', $request->cookie(ExcludedAccounts::COOKIE));
            $this->assertStringNotContainsString('googletagmanager.com', view('layouts.html.google.manager-head')->render());
        } finally {
            app()->instance('env', $environment);
        }
    }
}
