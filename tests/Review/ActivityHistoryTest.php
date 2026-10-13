<?php

namespace Tests\Review;

use App\Activity\Recorder;
use App\Http\Middleware\Logs\RecordWebLog;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Crypt, DB, Redis, Route};
use Illuminate\Support\Str;

class ActivityHistoryTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // Exercise already-persisted/legacy page tokens; default deferred
        // qualification is covered end-to-end in ActivityTrafficFilterTest.
        config(['activity.minimum_visit_seconds' => 0]);
        Route::middleware(['web', RecordWebLog::class])->group(function () {
            Route::get('/review/activity-page', function () { return response('<html>Activity page</html>'); })->name('review.activity-page');
            Route::post('/review/activity-action', function () { return response()->json(['ok' => true]); })->name('review.activity-action');
            Route::get('/review/activity-error', function () { abort(404); })->name('review.activity-error');
            Route::post('/review/activity-logout', function () { auth('web')->logout(); request()->session()->invalidate(); return response()->noContent(); })->name('review.activity-logout');
        });
        foreach (['/review/activity-summary' => 'review.activity-summary', '/api/review/activity-summary' => 'api.review.activity-summary'] as $uri => $name) {
            Route::middleware('web')->get($uri, function () {
                return response()->json(['count' => auth('web')->user()->logs_count, 'last' => optional(auth('web')->user()->lastActive())->timestamp]);
            })->name($name);
        }
    }

    private function user()
    {
        return Model::withoutEvents(function () { return create(User::class)->setAppends(['full_name']); });
    }

    private function open()
    {
        $this->get('/review/activity-page', ['Accept' => 'text/html'])->assertOk();
        return DB::table('activity_pages')->orderByDesc('opened_at')->first();
    }

    private function event($type = 'media_pause', $offset = 12000, array $metadata = [])
    {
        return ['id' => (string) Str::uuid(), 'type' => $type, 'elapsed_ms' => $offset, 'sequence' => 1, 'metadata' => $metadata];
    }

    public function test_guest_and_signed_in_page_views_use_sql_and_never_write_redis()
    {
        $page = $this->open();
        $this->assertNull($page->user_id);
        $this->assertDatabaseHas('activity_events', ['type' => 'page_view', 'source' => 'server', 'page_id' => $page->id, 'user_id' => null]);
        $user = $this->user();
        $this->actingAs($user, 'web');
        $this->open();
        $this->assertDatabaseHas('activity_events', ['type' => 'page_view', 'user_id' => $user->id]);
        $this->assertDatabaseCount('activity_visits', 2);
    }

    public function test_repeat_visits_in_one_second_are_not_overwritten_and_idle_visits_rotate()
    {
        $this->open(); $this->open();
        $this->assertDatabaseCount('activity_visits', 1);
        $this->assertDatabaseCount('activity_events', 2);
        $this->assertDatabaseCount('activity_pages', 2);
        $this->travel(31)->minutes(); $this->open();
        $this->assertDatabaseCount('activity_visits', 2);
    }

    public function test_media_offsets_context_and_retries_are_stored_without_duplicate_events()
    {
        $page = $this->open();
        $event = $this->event('media_pause', 12000, ['media_kind' => 'video', 'position_seconds' => 12, 'played_seconds' => 12, 'piece_id' => 42]);
        $payload = ['page_token' => Crypt::encryptString($page->id), 'events' => [$event], 'user_id' => 999, 'visit_id' => 'forged'];
        $this->travel(12)->seconds();
        $this->postJson('/activity/events', $payload)->assertNoContent();
        $lastActivity = DB::table('activity_visits')->where('id', $page->visit_id)->value('last_activity_at');
        $this->travel(1)->hours();
        $this->postJson('/activity/events', $payload)->assertNoContent();
        $this->assertSame($lastActivity, DB::table('activity_visits')->where('id', $page->visit_id)->value('last_activity_at'), 'A late retry must not lengthen the original visit.');
        $stored = DB::table('activity_events')->where('event_id', $event['id'])->first();
        $this->assertSame($page->visit_id, $stored->visit_id);
        $this->assertSame($page->id, $stored->page_id);
        $this->assertNull($stored->user_id);
        $this->assertSame(12000, (int) $stored->elapsed_ms);
        $this->assertSame(12, json_decode($stored->metadata, true)['position_seconds']);
        $this->assertSame(12000, (int) DB::table('activity_pages')->where('id', $page->id)->value('elapsed_ms'));
        $this->assertDatabaseCount('activity_events', 2);
    }

    public function test_page_tokens_cannot_be_used_by_another_account_or_browser_session()
    {
        $this->withExceptionHandling();
        $first = $this->user(); $second = $this->user();
        $this->actingAs($first, 'web'); $page = $this->open();
        $payload = ['page_token' => Crypt::encryptString($page->id), 'events' => [$this->event()]];
        $this->actingAs($second, 'web');
        $this->postJson('/activity/events', $payload)->assertForbidden();
        $this->actingAs($first, 'web')->withSession(['activity.visitor' => (string) Str::uuid()]);
        $this->postJson('/activity/events', $payload)->assertForbidden();
        $payload['page_token'] = 'forged-token';
        $this->postJson('/activity/events', $payload)->assertForbidden();
        $this->assertDatabaseCount('activity_events', 1);
    }

    public function test_expired_tokens_invalid_shapes_and_unbounded_browser_times_are_rejected()
    {
        $page = $this->open(); $token = Crypt::encryptString($page->id);
        $this->withExceptionHandling();
        $this->postJson('/activity/events', ['page_token' => $token, 'events' => [$this->event('favorite_changed')]])->assertStatus(422);
        $this->postJson('/activity/events', ['page_token' => $token, 'events' => [$this->event('media_pause', 0, ['position_seconds' => ['bad']])]])->assertStatus(422);
        $this->postJson('/activity/events', ['page_token' => $token, 'events' => [$this->event('click', 120000)]])->assertStatus(422);
        $this->travel(25)->hours();
        $this->postJson('/activity/events', ['page_token' => $token, 'events' => [$this->event()]])->assertForbidden();
    }

    public function test_new_metadata_excludes_credentials_and_correlates_successful_requests()
    {
        $page = $this->open();
        $this->postJson('/review/activity-action', ['password' => 'private', 'email' => 'secret@example.test', 'search' => 'Bach', 'filters' => ['level' => ['beginner'], 'token' => 'private']], ['X-Activity-Page' => Crypt::encryptString($page->id)])->assertOk();
        $event = DB::table('activity_events')->where('type', 'request')->first();
        $this->assertSame($page->visit_id, $event->visit_id);
        $this->assertSame($page->id, $event->page_id);
        $this->assertSame(200, (int) $event->status);
        $this->assertStringContainsString('Bach', $event->metadata);
        $this->assertStringNotContainsString('private', $event->metadata);
        $this->assertStringNotContainsString('secret@example', $event->metadata);
        $this->assertSame('https://pianolit.test/password/reset/[redacted]', \App\Activity\Metadata::url('https://pianolit.test/password/reset/private-token?email=secret@example.test'));
        $this->assertSame('https://pianolit.test/email/verify/[redacted]', \App\Activity\Metadata::url('https://pianolit.test/email/verify/123/private-hash?signature=private'));
    }

    public function test_failed_requests_are_recorded_as_failures_and_do_not_change_the_response()
    {
        $this->withExceptionHandling()->get('/review/activity-error')->assertNotFound();
        $this->assertDatabaseHas('activity_events', ['type' => 'request', 'status' => 404]);
    }

    public function test_browser_batch_rolls_back_completely_if_one_insert_fails()
    {
        $page = $this->open();
        DB::statement("CREATE TRIGGER activity_fail BEFORE INSERT ON activity_events WHEN NEW.type = 'media_pause' BEGIN SELECT RAISE(ABORT, 'simulated failure'); END");
        $payload = ['page_token' => Crypt::encryptString($page->id), 'events' => [$this->event('click', 0), $this->event('media_pause', 0)]];
        $payload['events'][1]['sequence'] = 2;
        $this->postJson('/activity/events', $payload)->assertStatus(503);
        $this->assertDatabaseCount('activity_events', 1);
        DB::statement('DROP TRIGGER activity_fail');
        $this->postJson('/activity/events', $payload)->assertNoContent();
        $this->assertDatabaseCount('activity_events', 3);
    }

    public function test_existing_mobile_logger_writes_unique_sql_requests_without_redis_or_fabricated_visits()
    {
        $this->app['request']->replace(['user_id' => 123, 'search' => 'Bach', 'password' => 'private', 'email' => 'secret@example.test']);
        $this->travelTo(now()->startOfSecond());
        (new \App\Log\Loggers\AppLog)->push();
        (new \App\Log\Loggers\AppLog)->push();
        $events = DB::table('activity_events')->get();
        $this->assertCount(2, $events);
        $this->assertNotSame($events[0]->event_id, $events[1]->event_id);
        $this->assertDatabaseCount('activity_visits', 0);
        $this->assertDatabaseCount('activity_pages', 0);
        $this->assertSame('app', $events[0]->platform);
        $this->assertSame(123, (int) $events[0]->user_id);
        $this->assertNull($events[0]->visit_id);
        $this->assertSame(['search' => 'Bach'], json_decode($events[0]->metadata, true)['data']);
        $this->assertFalse(json_decode($events[0]->metadata, true)['identity_verified']);
    }

    public function test_activity_endpoint_uses_web_csrf_and_admin_timelines_require_admin_authentication()
    {
        $route = Route::getRoutes()->getByName('activity.store');
        $this->assertContains('web', $route->gatherMiddleware());
        $this->assertNotContains('api', $route->gatherMiddleware());
        $this->assertContains('auth:admin', Route::getRoutes()->getByName('admin.activity.index')->gatherMiddleware());
        $this->assertContains('auth:admin', Route::getRoutes()->getByName('admin.activity.show')->gatherMiddleware());
        $this->assertFalse((new Recorder)->eligible(\Illuminate\Http\Request::create('http://admin.'.config('app.short_url').'/activity')));
    }

    public function test_real_csrf_verification_rejects_a_missing_token()
    {
        $middleware = new class($this->app, $this->app['encrypter']) extends \App\Http\Middleware\VerifyCsrfToken {
            protected function runningUnitTests() { return false; }
        };
        $request = \Illuminate\Http\Request::create('/activity/events', 'POST');
        $session = $this->app['session']->driver(); $session->start(); $session->put('_token', 'review-csrf');
        $request->setLaravelSession($session);
        $this->expectException(\Illuminate\Session\TokenMismatchException::class);
        $middleware->handle($request, function () { return response()->noContent(); });
    }

    public function test_admin_can_read_anonymous_and_account_visit_timelines_and_database_counts()
    {
        $page = $this->open();
        $this->actingAs(create(\App\Admin::class), 'admin');
        $this->get(route('admin.activity.index'))->assertOk()->assertSee('Anonymous')->assertSee('Open timeline');
        $this->get(route('admin.activity.show', $page->visit_id))->assertOk()->assertSee('Visit journey')->assertSee('/review/activity-page', false);
    }

    public function test_favorite_events_record_the_confirmed_add_and_remove_result()
    {
        $this->withoutMiddleware(\App\Http\Middleware\UpdateLocation::class);
        $user = $this->user();
        $piece = Model::withoutEvents(function () { return create(\App\Piece::class); });
        $this->actingAs($user, 'web');
        $this->get('http://my.'.config('app.short_url').'/review/activity-page')->assertOk();
        $page = DB::table('activity_pages')->first();
        $headers = ['X-Activity-Page' => Crypt::encryptString($page->id)];
        $this->postJson(route('webapp.users.favorites.update', $piece), [], $headers)->assertOk()->assertJson(['status' => true]);
        $this->postJson(route('webapp.users.favorites.update', $piece), [], $headers)->assertOk()->assertJson(['status' => false]);
        $events = DB::table('activity_events')->where('type', 'favorite_changed')->orderBy('id')->get();
        $this->assertCount(2, $events);
        $this->assertSame([true, false], $events->map(function ($event) { return json_decode($event->metadata, true)['result']; })->all());
        $this->assertSame($page->id, $events[0]->page_id);
        $this->assertSame((string) $piece->id, $events[0]->resource_id);
    }

    public function test_admin_activity_overview_orders_and_counts_using_sql()
    {
        $user = $this->user(); $this->actingAs($user, 'web'); $this->open();
        $this->actingAs(create(\App\Admin::class), 'admin');
        $url = route('admin.users.logs').'?'.http_build_query(['draw' => 1, 'order' => [['column' => 3, 'dir' => 'desc']], 'columns' => [3 => ['data' => 'visits']]]);
        $this->getJson($url, ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertJsonPath('data.0.visits', 1)->assertJsonPath('data.0.id', $user->id);
    }

    public function test_web_and_api_account_activity_attributes_use_sql_with_the_same_response_fields()
    {
        $user = $this->user(); $this->actingAs($user, 'web'); $this->open();
        $this->getJson('/review/activity-summary')->assertOk()->assertJsonPath('count', 1);
        $last = carbon(DB::table('activity_events')->value('occurred_at'))->timestamp;
        $this->getJson('/api/review/activity-summary')->assertOk()->assertExactJson(['count' => 1, 'last' => $last]);
    }

    public function test_logout_keeps_the_actor_and_visitor_who_performed_the_action()
    {
        $user = $this->user(); $this->actingAs($user, 'web'); $page = $this->open();
        $this->postJson('/review/activity-logout', [], ['X-Activity-Page' => Crypt::encryptString($page->id)])->assertNoContent();
        $this->assertDatabaseHas('activity_events', ['route_name' => 'review.activity-logout', 'user_id' => $user->id, 'visitor_id' => $page->visitor_id, 'visit_id' => $page->visit_id]);
    }
}
