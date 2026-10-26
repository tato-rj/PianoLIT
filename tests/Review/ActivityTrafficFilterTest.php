<?php

namespace Tests\Review;

use App\Activity\{NoisePruner, PendingVisits};
use App\{Piece, User};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Crypt, DB, Route};
use Illuminate\Support\Str;

class ActivityTrafficFilterTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->travelTo(\Carbon\Carbon::parse('2026-10-12T20:00:00Z'));
        $this->withExceptionHandling();
        Route::middleware(['web', \App\Http\Middleware\Logs\RecordWebLog::class])->group(function () {
            Route::get('/review/traffic/{number?}', function () { return response('<html>'.view('components.tracking.activity')->render().'</html>'); })->name('review.traffic');
            Route::get('/review/traffic-error', function () { abort(404); })->name('review.traffic-error');
            Route::get('/review/traffic-piece/{piece}', function (Piece $piece) { return response('<html>'.view('components.tracking.activity')->render().'</html>'); })->name('review.traffic-piece');
        });
    }

    private function open($number = 1, $host = null)
    {
        $response = $this->get(($host ?: 'http://'.config('app.short_url')).'/review/traffic/'.$number)->assertOk();
        return $this->context($response);
    }

    private function context($response)
    {
        preg_match('/window\.pianoActivityConfig = (.*);/', $response->getContent(), $match);
        $config = json_decode($match[1], true);
        $this->assertLessThanOrEqual(4096, strlen($config['pageToken']));
        $request = $this->app['request'];
        $request->setLaravelSession(session()->driver());
        $page = (new \App\Activity\Recorder)->pageFromToken($request, $config['pageToken']);
        $page->_token = $config['pageToken'];
        return $page;
    }

    private function event($seconds, $visible = null, $type = 'page_checkpoint', $id = null, array $extra = [])
    {
        return ['id' => $id ?: (string) Str::uuid(), 'type' => $type, 'elapsed_ms' => $seconds * 1000, 'sequence' => 1,
            'metadata' => array_merge(['visible_ms' => ($visible ?? $seconds) * 1000], $extra)];
    }

    private function send($page, $seconds, $visible = null, $type = 'page_checkpoint', $id = null, array $extra = [], array $qualification = [])
    {
        return $this->postJson(($page->platform === 'webapp' ? 'http://my.' : 'http://').config('app.short_url').'/activity/events',
            ['page_token' => $page->_token, 'events' => [$this->event($seconds, $visible, $type, $id, $extra)], 'qualification' => $qualification]);
    }

    public function test_default_gate_never_saves_short_requests_and_promotes_at_five_seconds()
    {
        $page = $this->open();
        $this->assertNotNull($page);
        $this->assertDatabaseCount('activity_visits', 0);
        $this->assertDatabaseCount('activity_pages', 0);
        $this->send($page, 0, 0, 'page_ready')->assertStatus(202);
        $this->travel(2)->seconds();
        $this->send($page, 2, 2, 'page_leave')->assertStatus(202);
        $this->assertDatabaseCount('activity_events', 0);
        $this->travel(3)->seconds();
        $this->send($page, 5)->assertNoContent();
        $this->assertDatabaseCount('activity_visits', 1);
        $this->assertDatabaseCount('activity_pages', 1);
        $this->assertDatabaseCount('activity_events', 2);
        $this->assertSame(5000, (int) DB::table('activity_pages')->value('visible_ms'));
        $this->assertDatabaseHas('activity_events', ['type' => 'page_view', 'page_id' => $page->id, 'source' => 'server']);
        $this->assertNull(session(PendingVisits::KEY));
    }

    public function test_deferred_browser_pages_cannot_be_promoted_after_the_account_is_purged()
    {
        $user = Model::withoutEvents(function () { return create(User::class); });
        $this->actingAs($user, 'web'); $page = $this->open();
        (new \App\Services\Admin\RemoveUser)->remove($user->id, $user->email, true);
        $this->travel(6)->seconds();
        $this->send($page, 6)->assertNoContent();
        foreach (['activity_visits', 'activity_pages', 'activity_events'] as $table) $this->assertDatabaseCount($table, 0);
    }

    public function test_foreground_time_accumulates_across_pages_without_losing_the_opening_story()
    {
        $first = $this->open(1);
        $this->travel(2)->seconds();
        $leave = $this->event(2, 2, 'page_leave');
        $second = $this->open(2);
        $this->assertSame($first->visit_id, $second->visit_id);
        $this->travel(3)->seconds();
        $proof = ['page_token' => $first->_token, 'event' => $leave];
        $this->send($second, 3, 3, 'page_checkpoint', null, [], [$proof])->assertNoContent();
        $this->assertDatabaseCount('activity_visits', 1);
        $this->assertDatabaseCount('activity_pages', 2);
        $this->assertDatabaseCount('activity_events', 4);
        $this->assertSame([2000, 3000], DB::table('activity_pages')->orderBy('opened_at')->pluck('visible_ms')->map(function ($v) { return (int) $v; })->all());
    }

    public function test_signed_opening_context_preserves_the_route_bound_piece_and_real_metric()
    {
        $piece = Model::withoutEvents(function () { return create(Piece::class); });
        $page = $this->context($this->get('http://'.config('app.short_url').'/review/traffic-piece/'.$piece->id)->assertOk());
        $this->travel(5)->seconds(); $this->send($page, 5)->assertNoContent();
        $this->assertDatabaseHas('activity_events', ['type' => 'page_view', 'source' => 'server', 'resource_type' => 'piece', 'resource_id' => $piece->id]);
        $filters = new \App\Activity\Filters(\Illuminate\Http\Request::create('/', 'GET', ['range' => 'today', 'audience' => 'all']));
        $this->assertSame(1, (new \App\Activity\Queries)->metrics($filters)['Pieces opened']);
    }

    public function test_hidden_time_and_initial_readiness_do_not_qualify_but_actual_playback_does()
    {
        $page = $this->open();
        $this->travel(10)->minutes();
        $this->send($page, 600, 0, 'page_hidden')->assertStatus(202);
        $this->assertDatabaseCount('activity_visits', 0);
        $this->send($page, 600, 0, 'media_progress', null, ['media_kind' => 'audio', 'played_seconds' => 5])->assertNoContent();
        $this->assertDatabaseCount('activity_visits', 1);
    }

    /** @dataProvider botAgents */
    public function test_known_crawlers_and_automated_clients_are_excluded_without_blocking_pages($agent)
    {
        $this->withHeader('User-Agent', $agent)->get('http://'.config('app.short_url').'/review/traffic')->assertOk()->assertDontSee('pianoActivityConfig');
        $this->assertDatabaseCount('activity_visits', 0);
        $this->assertDatabaseCount('activity_events', 0);
        $this->assertEmpty(session(PendingVisits::KEY, []));
    }

    public static function botAgents()
    {
        return [['Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'], ['Mozilla/5.0 (compatible; bingbot/2.0)'],
            ['Mozilla/5.0 AppleWebKit/537.36 HeadlessChrome/130.0'], ['python-requests/2.32.0'], ['curl/8.0'], ['GPTBot/1.2'], ['Example crawler/1.0']];
    }

    public function test_normal_mobile_device_names_are_not_confused_with_bot_tokens_and_ingestion_is_filtered()
    {
        $this->withHeader('User-Agent', 'Mozilla/5.0 (Linux; Android 12; CUBOT) AppleWebKit/537.36 Chrome/130.0 Mobile Safari/537.36');
        $page = $this->open();
        $this->travel(5)->seconds();
        $this->withHeader('User-Agent', 'Googlebot/2.1');
        $this->send($page, 5)->assertNoContent();
        $this->assertDatabaseCount('activity_visits', 0);
    }

    public function test_pending_tokens_still_require_the_correct_account_and_session()
    {
        $page = $this->open();
        $user = Model::withoutEvents(function () { return create(User::class); });
        $this->actingAs($user, 'web');
        $this->send($page, 0)->assertForbidden();
        auth('web')->logout();
        $this->withSession(['activity.visitor' => (string) Str::uuid()]);
        $this->send($page, 0)->assertForbidden();
        $this->assertDatabaseCount('activity_events', 0);
    }

    public function test_qualification_retry_is_idempotent_and_replaying_another_pages_ids_cannot_create_noise()
    {
        $page = $this->open(); $id = (string) Str::uuid();
        $this->travel(5)->seconds();
        $this->send($page, 5, 5, 'page_checkpoint', $id)->assertNoContent();
        $this->send($page, 5, 5, 'page_checkpoint', $id)->assertNoContent();
        $this->assertDatabaseCount('activity_visits', 1);
        $this->assertDatabaseCount('activity_events', 2);
        $this->travel(31)->minutes();
        $other = $this->open(2);
        $this->travel(5)->seconds();
        $this->send($other, 5, 5, 'page_checkpoint', $id)->assertStatus(202);
        $this->assertDatabaseCount('activity_visits', 1);
        $this->assertDatabaseCount('activity_pages', 1);
    }

    public function test_promotion_rolls_back_and_retries_the_original_opening_and_events()
    {
        $page = $this->open();
        \Log::shouldReceive('error')->once();
        DB::statement("CREATE TRIGGER promotion_fail BEFORE INSERT ON activity_events WHEN NEW.type = 'page_checkpoint' BEGIN SELECT RAISE(ABORT, 'simulated failure'); END");
        $this->travel(5)->seconds(); $id = (string) Str::uuid();
        $this->send($page, 5, 5, 'page_checkpoint', $id)->assertStatus(503);
        $this->assertDatabaseCount('activity_visits', 0);
        $this->assertDatabaseCount('activity_pages', 0);
        DB::statement('DROP TRIGGER promotion_fail');
        $this->send($page, 5, 5, 'page_checkpoint', $id)->assertNoContent();
        $this->assertDatabaseCount('activity_events', 2);
    }

    public function test_pending_buffer_is_bounded_and_failed_requests_create_no_analytics_rows()
    {
        for ($i = 0; $i < 40; $i++) $this->open($i);
        $this->assertArrayNotHasKey('page', session(PendingVisits::KEY));
        $this->assertArrayNotHasKey('events', session(PendingVisits::KEY));
        $this->assertLessThanOrEqual(1024, strlen(json_encode(session(PendingVisits::KEY))));
        $this->get('http://'.config('app.short_url').'/review/traffic-error')->assertNotFound();
        $this->assertDatabaseCount('activity_visits', 0);
        $this->assertDatabaseCount('activity_pages', 0);
        $this->assertDatabaseCount('activity_events', 0);
    }

    public function test_failed_initial_request_cannot_supply_a_false_start_time_for_a_later_visit()
    {
        $this->get('http://'.config('app.short_url').'/review/traffic-error')->assertNotFound();
        $this->assertNull(session(PendingVisits::KEY));
        $this->travel(10)->minutes(); $page = $this->open();
        $this->assertSame(now('UTC')->format('Y-m-d H:i:s.v'), $page->_visit['started_at_utc']);
        $this->travel(5)->seconds(); $this->send($page, 5)->assertNoContent();
        $this->assertSame($page->opened_at_utc, DB::table('activity_visits')->value('started_at_utc'));
    }

    public function test_confirmed_favorite_is_preserved_without_creating_a_zero_second_visit()
    {
        $this->withoutMiddleware([\App\Http\Middleware\UpdateLocation::class]);
        [$user, $piece] = Model::withoutEvents(function () { return [create(User::class), create(Piece::class)]; });
        $this->actingAs($user, 'web'); $page = $this->open(1, 'http://my.'.config('app.short_url'));
        $this->postJson(route('webapp.users.favorites.update', $piece), [], ['X-Activity-Page' => $page->_token])->assertOk();
        $this->assertDatabaseHas('activity_events', ['type' => 'favorite_changed', 'user_id' => $user->id, 'visit_id' => null, 'page_id' => null]);
        $this->assertDatabaseCount('activity_visits', 0);
    }

    public function test_browser_event_budget_stops_repeated_noise_after_a_visit_has_qualified()
    {
        config(['activity.max_browser_events_per_page' => 2]);
        $page = $this->open();
        $this->travel(5)->seconds(); $this->send($page, 5)->assertNoContent();
        $this->send($page, 5, 5, 'click')->assertNoContent();
        $this->send($page, 5, 5, 'click')->assertNoContent();
        $this->assertSame(2, DB::table('activity_events')->where('source', 'browser')->count());
    }

    public function test_old_unverified_visit_cannot_grandfather_future_requests_past_the_gate()
    {
        [$old] = $this->oldVisit(2000, 'web', true);
        $visitor = DB::table('activity_visits')->where('id', $old)->value('visitor_id');
        $this->withSession(['activity.visit' => $old, 'activity.visitor' => $visitor]);
        $page = $this->open();
        $this->assertNotSame($old, $page->visit_id);
        $this->assertDatabaseCount('activity_visits', 1);
        $this->assertDatabaseCount('activity_pages', 1);
        $this->send($page, 0, 0, 'page_ready')->assertStatus(202);
        $this->travel(5)->seconds(); $this->send($page, 5)->assertNoContent();
        $this->assertDatabaseCount('activity_visits', 2);
        $this->get('http://'.config('app.short_url').'/review/traffic/2')->assertOk();
        $this->assertDatabaseCount('activity_visits', 2);
        $this->assertDatabaseCount('activity_pages', 3);
    }

    public function test_large_context_tokens_remain_bounded_and_qualification_cannot_borrow_another_visit()
    {
        $first = $this->open(str_repeat('a', 1800));
        $this->travel(31)->minutes();
        $second = $this->open(2);
        $this->travel(5)->seconds();
        $proof = ['page_token' => $first->_token, 'event' => $this->event(5)];
        $this->send($second, 0, 0, 'page_ready', null, [], [$proof])->assertForbidden();
        $this->assertDatabaseCount('activity_visits', 0);
        $second->_token .= 'tampered';
        $this->send($second, 5)->assertForbidden();
    }

    public function test_reusing_one_event_id_for_conflicting_page_contexts_is_rejected()
    {
        $first = $this->open(1);
        $this->travel(2)->seconds(); $second = $this->open(2);
        $this->travel(3)->seconds(); $id = (string) Str::uuid();
        $proof = ['page_token' => $first->_token, 'event' => $this->event(2, 2, 'page_leave', $id)];
        $this->send($second, 3, 3, 'page_checkpoint', $id, [], [$proof])->assertStatus(422);
        $this->assertDatabaseCount('activity_visits', 0);
    }

    public function test_head_requests_do_not_create_activity_and_pending_html_is_private()
    {
        $url = 'http://'.config('app.short_url').'/review/traffic';
        $this->call('HEAD', $url)->assertOk()->assertDontSee('pianoActivityConfig');
        $this->assertDatabaseCount('activity_events', 0);
        $this->get($url)->assertHeader('Cache-Control', 'no-store, private');
    }

    private function oldVisit($milliseconds, $platform = 'web', $recent = false)
    {
        $id = (string) Str::uuid(); $page = (string) Str::uuid(); $visitor = (string) Str::uuid();
        $start = now('UTC')->subMinutes($recent ? 10 : 120); $last = $start->copy()->addMilliseconds($milliseconds);
        DB::table('activity_visits')->insert(['id' => $id, 'visitor_id' => $visitor, 'platform' => $platform,
            'started_at' => $start->copy()->timezone(config('app.timezone')), 'last_activity_at' => $last->copy()->timezone(config('app.timezone')),
            'started_at_utc' => $start->format('Y-m-d H:i:s.v'), 'last_activity_at_utc' => $last->format('Y-m-d H:i:s.v')]);
        DB::table('activity_pages')->insert(['id' => $page, 'visit_id' => $id, 'visitor_id' => $visitor, 'platform' => $platform, 'url' => 'https://example.test/piece', 'opened_at' => $start->copy()->timezone(config('app.timezone')), 'opened_at_utc' => $start->format('Y-m-d H:i:s.v')]);
        DB::table('activity_events')->insert(['event_id' => (string) Str::uuid(), 'visit_id' => $id, 'page_id' => $page, 'platform' => $platform, 'type' => 'page_view', 'source' => 'server', 'occurred_at' => $start, 'received_at' => $start]);
        return [$id, $page];
    }

    public function test_cleanup_preview_and_execution_remove_only_old_short_web_history_and_keep_outcomes()
    {
        [$short, $page] = $this->oldVisit(0);
        [$two] = $this->oldVisit(2000); [$boundary] = $this->oldVisit(4999);
        [$qualified] = $this->oldVisit(5000); [$recent] = $this->oldVisit(0, 'web', true);
        [$mobile] = $this->oldVisit(0, 'app'); [$inconsistent, $observedPage] = $this->oldVisit(0);
        DB::table('activity_pages')->where('id', $observedPage)->update(['visible_ms' => 9000]);
        $user = Model::withoutEvents(function () { return create(User::class); });
        DB::table('activity_visit_links')->insert(['visit_id' => $short, 'user_id' => $user->id, 'linked_at_utc' => now('UTC'), 'reason' => 'login']);
        DB::table('activity_events')->insert(['event_id' => (string) Str::uuid(), 'visit_id' => $short, 'page_id' => $page, 'user_id' => $user->id, 'platform' => 'web', 'type' => 'favorite_changed', 'source' => 'server', 'received_at' => now(), 'metadata' => '{"result":true}']);
        $cutoff = now('UTC')->subHour(); $pruner = new NoisePruner;
        $preview = $pruner->run($cutoff, 5000);
        $this->assertSame(3, $preview['visits']);
        $this->assertSame(1, $preview['preserved_events']);
        $this->assertDatabaseCount('activity_visits', 7);
        $this->assertSame($preview, $pruner->run($cutoff, 5000, true, 1));
        foreach ([$short, $two, $boundary] as $id) $this->assertDatabaseMissing('activity_visits', ['id' => $id]);
        foreach ([$qualified, $recent, $mobile, $inconsistent] as $id) $this->assertDatabaseHas('activity_visits', ['id' => $id]);
        $this->assertDatabaseMissing('activity_visit_links', ['visit_id' => $short]);
        $this->assertDatabaseMissing('activity_pages', ['id' => $page]);
        $this->assertDatabaseHas('activity_events', ['type' => 'favorite_changed', 'user_id' => $user->id, 'visit_id' => null, 'page_id' => null]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertSame(0, $pruner->run($cutoff, 5000, true)['visits']);
    }

    public function test_cleanup_transaction_rolls_back_all_tables_when_a_delete_fails()
    {
        [$visit, $page] = $this->oldVisit(1000);
        DB::statement("CREATE TRIGGER prune_fail BEFORE DELETE ON activity_pages BEGIN SELECT RAISE(ABORT, 'simulated failure'); END");
        try { (new NoisePruner)->run(now('UTC')->subHour(), 5000, true); $this->fail('Expected cleanup failure.'); }
        catch (\Illuminate\Database\QueryException $exception) {
            $this->assertDatabaseHas('activity_visits', ['id' => $visit]);
            $this->assertDatabaseHas('activity_pages', ['id' => $page]);
            $this->assertDatabaseHas('activity_events', ['visit_id' => $visit]);
        }
    }

    public function test_cleanup_handles_original_local_timestamps_but_protects_newly_opened_pages()
    {
        [$legacy] = $this->oldVisit(2000);
        DB::table('activity_visits')->where('id', $legacy)->update(['started_at_utc' => null, 'last_activity_at_utc' => null]);
        [$live, $page] = $this->oldVisit(0);
        DB::table('activity_pages')->where('id', $page)->update(['opened_at' => now(), 'opened_at_utc' => now('UTC')]);
        $result = (new NoisePruner)->run(now('UTC')->subHour(), 5000, true);
        $this->assertSame(1, $result['visits']);
        $this->assertDatabaseMissing('activity_visits', ['id' => $legacy]);
        $this->assertDatabaseHas('activity_visits', ['id' => $live]);
    }

    public function test_cleanup_command_defaults_to_preview_validates_options_and_respects_run_limit()
    {
        $this->oldVisit(0); $this->oldVisit(2000);
        $this->artisan('activity:prune-noise')->expectsOutput('PREVIEW ONLY — no database writes.')->assertExitCode(0);
        $this->assertDatabaseCount('activity_visits', 2);
        $this->artisan('activity:prune-noise', ['--execute' => true, '--older-than' => 0])->assertExitCode(1);
        $this->assertDatabaseCount('activity_visits', 2);
        $this->artisan('activity:prune-noise', ['--execute' => true, '--batch' => 1, '--limit' => 1])->assertExitCode(0);
        $this->assertDatabaseCount('activity_visits', 1);
        $this->assertDatabaseCount('activity_pages', 1);
        $this->assertDatabaseCount('activity_events', 1);
    }
}
