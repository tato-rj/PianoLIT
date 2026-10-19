<?php

namespace Tests\Review;

use App\{Admin, User};
use App\Activity\{Filters, Queries};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Redis};
use Illuminate\Support\Str;

class IosActivityAnalyticsTest extends ReviewTestCase
{
    private function account(array $attributes = [])
    {
        return Model::withoutEvents(function () use ($attributes) { return create(User::class, $attributes); });
    }

    private function filters(array $values = [])
    {
        return new Filters(Request::create('/', 'GET', $values + ['range' => '30', 'platform' => 'app', 'audience' => 'registered']));
    }

    private function event($userId, $at, array $attributes = [])
    {
        return DB::table('activity_events')->insertGetId($attributes + [
            'event_id' => (string) Str::uuid(), 'user_id' => $userId, 'platform' => 'app', 'type' => 'request',
            'source' => 'server', 'occurred_at' => $at, 'received_at' => '2026-10-13 23:00:00',
            'url' => 'https://pianolit.com/api/discover', 'metadata' => '{}',
        ]);
    }

    public function test_ios_history_resolves_reported_accounts_for_current_and_imported_requests_without_verifying_them()
    {
        $this->travelTo(carbon('2026-10-13 22:58:00'));
        $user = $this->account(['first_name' => 'Alex', 'last_name' => 'Rivera', 'email' => 'alex@example.com']);
        $current = $this->event($user->id, '2026-10-13 12:00:00', ['metadata' => '{"identity_verified":false}']);
        $imported = $this->event($user->id, '2026-10-12 12:00:00', ['type' => 'legacy_request', 'source' => 'redis']);
        $this->actingAs(create(Admin::class), 'admin');
        foreach ([['platform' => 'app'], ['platform' => 'all', 'history' => 1]] as $filters) {
            $response = $this->get(route('admin.analytics.users', $filters + ['range' => '30']))->assertOk()
                ->assertSee('Alex Rivera')->assertSee('alex@example.com')
                ->assertSee(route('admin.analytics.users.profile', $user->id))
                ->assertSee('association unverified');
            $this->assertSame([$current, $imported], $response->viewData('history')->pluck('id')->all());
        }
        $this->assertDatabaseHas('activity_events', ['id' => $current, 'user_id' => $user->id, 'visit_id' => null, 'metadata' => '{"identity_verified":false}']);
        $this->assertDatabaseCount('activity_visits', 0);
        $this->assertDatabaseCount('activity_visit_links', 0);
        $this->get(route('admin.analytics.users.profile', ['user' => $user->id, 'tab' => 'visits', 'range' => '30', 'platform' => 'app']))
            ->assertOk()->assertSee('Ungrouped activity')->assertSee('2 records in this period');
    }

    public function test_ios_history_keeps_missing_and_unknown_accounts_unlinked_and_escapes_account_names()
    {
        $this->travelTo(carbon('2026-10-13 22:58:00'));
        $user = $this->account(['first_name' => '<script>alert(1)</script>', 'last_name' => 'Rivera', 'email' => 'safe@example.com']);
        $this->event($user->id, '2026-10-13 12:00:00');
        $this->event(999999, '2026-10-13 12:01:00');
        $this->event(null, '2026-10-13 12:02:00');
        $this->actingAs(create(Admin::class), 'admin');
        $this->get(route('admin.analytics.users', ['range' => '30', 'platform' => 'app', 'audience' => 'all']))->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt; Rivera', false)->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('Reported account #999999')->assertSee('Account unknown')
            ->assertDontSee(route('admin.analytics.users.profile', 999999));
    }

    public function test_reported_account_details_remain_admin_only_and_respect_analytics_exclusions()
    {
        $this->travelTo(carbon('2026-10-13 22:58:00'));
        $user = $this->account(['email' => 'ordinary@example.com']);
        $excluded = $this->account(['first_name' => 'Excluded', 'last_name' => 'Account', 'email' => ' Elena.Dare@Gmail.com ']);
        $this->event($user->id, '2026-10-13 12:00:00');
        $this->event($excluded->id, '2026-10-13 12:01:00');
        $url = route('admin.analytics.users', ['range' => '30', 'platform' => 'app']);
        $this->withExceptionHandling()->get($url)->assertRedirect();
        $this->actingAs($user, 'web')->get($url)->assertRedirect();
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get($url)->assertOk()->assertSee('ordinary@example.com')
            ->assertDontSee('Excluded Account')->assertDontSee('Elena.Dare@Gmail.com')
            ->assertDontSee(route('admin.analytics.users.profile', $excluded->id));
        $this->assertSame(1, $response->viewData('history')->total());
    }

    public function test_existing_mobile_get_post_and_failed_requests_are_saved_and_appear_in_the_ios_chart()
    {
        $this->travelTo(carbon('2026-10-13 22:58:00'));
        $user = $this->account();
        $this->getJson(route('api.users.favorites.show', ['user_id' => $user->id]))->assertOk()->assertExactJson([]);
        $this->postJson(route('api.users.favorites.show'), ['user_id' => $user->id])->assertOk()->assertExactJson([]);
        $this->withExceptionHandling()->getJson(route('api.pieces.find', ['user_id' => $user->id, 'search' => 999999]))->assertNotFound();
        $events = DB::table('activity_events')->get();
        $this->assertCount(3, $events);
        foreach ($events as $event) {
            $this->assertSame('app', $event->platform);
            $this->assertSame('request', $event->type);
            $this->assertSame('server', $event->source);
            $this->assertNull($event->visit_id);
            $this->assertFalse(json_decode($event->metadata, true)['identity_verified']);
        }
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.analytics.users', ['range' => '30', 'platform' => 'app']))->assertOk()
            ->assertSee('2026-10-13 · iOS requests: 3')->assertSee('Latest current-logger request: Oct 13, 2026 · 10:58:00 PM.')
            ->assertSee('Recent iOS requests')->assertSee('Submitted identity · unverified')->assertDontSee('No visits recorded');
        $this->assertSame(['iOS requests' => 3, 'Reported accounts' => 1, 'Days with requests' => 1], $response->viewData('metrics'));
        $this->assertSame(['ios_requests' => ['iOS requests', 'app']], $response->viewData('series'));
        $this->assertSame(3, array_sum(array_column($response->viewData('trend'), 'ios_requests')));
        $this->assertSame($events->pluck('id')->reverse()->values()->all(), $response->viewData('history')->pluck('id')->all());
        $this->assertDatabaseCount('activity_visits', 0);
        $this->assertDatabaseCount('activity_pages', 0);
    }

    public function test_redis_imported_requests_use_the_original_day_in_the_chart_and_do_not_imply_live_collection()
    {
        $this->travelTo(carbon('2026-10-13 22:58:00'));
        $user = $this->account();
        $redis = \Mockery::mock();
        Redis::shouldReceive('connection')->with('default')->andReturn($redis);
        $prefix = config('database.redis.prefix'); $key = $prefix.'user:'.$user->id.':app';
        $timestamp = (string) carbon('2026-09-14 23:59:59')->timestamp;
        $payload = '{"url":"https://pianolit.com/api/pieces/find","data":{"search":42}}';
        $redis->shouldReceive('scan')->with('0', 'MATCH', $prefix.'*', 'COUNT', 500)->once()->andReturn(['0', [$key]]);
        $redis->shouldReceive('scan')->with('0', 'MATCH', $prefix.'user:*', 'COUNT', 500)->once()->andReturn(['0', [$key]]);
        $redis->shouldReceive('type')->with($key)->once()->andReturn('hash');
        $redis->shouldReceive('hscan')->with($key, '0', 'COUNT', 500)->twice()->andReturn(['0', [$timestamp => $payload]]);
        $this->artisan('activity:import-redis', ['--before' => (string) (time() - 60), '--sleep-ms' => 0, '--verify' => true])->assertExitCode(0);
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.analytics.users', ['range' => '30', 'platform' => 'app']))->assertOk()
            ->assertSee('2026-09-14 · iOS requests: 1')->assertSee('0 iOS requests from the current SQL logger and 1 imported requests match this period.')
            ->assertSee('No requests from the current SQL logger are recorded in this period.');
        $this->assertSame(1, $response->viewData('metrics')['iOS requests']);
        $trend = collect($response->viewData('trend'))->keyBy('day');
        $this->assertSame(1, $trend['2026-09-14']['ios_requests']);
        $this->assertSame(0, $trend['2026-10-13']['ios_requests'], 'Receipt/import day does not replace the request date.');
        $this->assertDatabaseHas('activity_events', ['legacy_payload' => $payload, 'visit_id' => null]);
    }

    public function test_request_series_and_summary_share_date_audience_platform_and_exclusion_scopes()
    {
        $this->travelTo(carbon('2026-10-13 22:58:00'));
        $user = $this->account(); $other = $this->account(); $excluded = $this->account(['email' => ' ARTHURVILLAR@GMAIL.COM ']);
        $this->event($user->id, '2026-09-14 00:00:00');
        $this->event($user->id, '2026-10-13 23:59:59');
        $this->event($other->id, '2026-10-13 12:00:00', ['type' => 'legacy_request', 'source' => 'redis']);
        $this->event($user->id, '2026-09-13 23:59:59');
        $this->event($user->id, '2026-10-14 00:00:00');
        $this->event($user->id, null, ['type' => 'legacy_unparsed', 'source' => 'redis']);
        $this->event($user->id, '2026-10-13 12:00:00', ['platform' => 'webapp']);
        $this->event($user->id, '2026-10-13 12:00:00', ['type' => 'media_play', 'source' => 'browser']);
        $this->event($excluded->id, '2026-10-13 12:00:00');
        $this->event(null, '2026-10-13 12:00:00', ['type' => 'legacy_request', 'source' => 'redis']);
        DB::table('activity_legacy_daily')->insert(['id' => hash('sha256', 'ios-daily'), 'date' => '2026-10-13', 'platform' => 'app', 'total' => 10000, 'source_key' => 'logs:daily:2026-10-13:app', 'captured_at' => now()]);
        $queries = new Queries;
        foreach (['registered' => 3, 'anonymous' => 1, 'all' => 4] as $audience => $expected) {
            $filters = $this->filters(['audience' => $audience]);
            $summary = $queries->appRequestSummary($filters);
            $trend = $queries->trend($filters);
            $this->assertSame($expected, $summary->requests);
            $this->assertSame($expected, array_sum(array_column($trend, 'ios_requests')));
            foreach (['total', 'pieces', 'media_starts'] as $key) $this->assertSame(0, array_sum(array_column($trend, $key)), 'API requests never become visits, piece openings or playback.');
        }
        $summary = $queries->appRequestSummary($this->filters());
        $this->assertSame(2, $summary->reported_accounts);
        $this->assertSame(2, $summary->active_days);
        $this->assertSame(2, $summary->current_requests);
        $this->assertSame(1, $summary->imported_requests);
        $this->assertSame('2026-10-13 23:59:59', $summary->latest_current_request);
        foreach (['web', 'webapp'] as $platform) {
            $this->assertSame(0, array_sum(array_column($queries->trend($this->filters(compact('platform'))), 'ios_requests')));
            $this->assertSame(0, $queries->appRequestSummary($this->filters(compact('platform')))->requests);
        }
    }

    public function test_all_platforms_chart_adds_ios_requests_without_changing_browser_metrics_or_adding_visits()
    {
        $this->travelTo(carbon('2026-10-13 22:58:00'));
        $user = $this->account();
        $visit = (string) Str::uuid();
        DB::table('activity_visits')->insert(['id' => $visit, 'user_id' => $user->id, 'visitor_id' => (string) Str::uuid(), 'platform' => 'webapp', 'started_at' => '2026-10-13 12:00:00', 'last_activity_at' => '2026-10-13 12:01:00']);
        $this->event($user->id, '2026-10-13 12:00:00', ['visit_id' => $visit, 'platform' => 'webapp', 'type' => 'page_view', 'resource_type' => 'piece', 'resource_id' => '42']);
        $this->event($user->id, '2026-10-13 12:00:00', ['visit_id' => $visit, 'platform' => 'webapp', 'type' => 'media_play', 'source' => 'browser']);
        $this->event($user->id, '2026-10-13 12:01:00');
        $this->event($user->id, '2026-10-13 12:02:00', ['type' => 'legacy_request', 'source' => 'redis']);
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.analytics.users', ['range' => '30', 'platform' => 'all']))->assertOk()
            ->assertSee('Visits: 1, Pieces opened: 1, Media starts: 1, iOS requests: 2');
        $this->assertSame(['Unique visitors' => 1, 'Visits' => 1, 'Returning visitors' => 0, 'Pieces opened' => 1, 'Media starts' => 1, 'iOS requests' => 2], $response->viewData('metrics'));
        $this->assertCount(4, $response->viewData('series'));
        $browser = $this->get(route('admin.analytics.users', ['range' => '30', 'platform' => 'webapp']))->assertOk();
        $this->assertCount(3, $browser->viewData('series'));
        $this->assertSame(1, $browser->viewData('metrics')['Visits']);
        $this->assertArrayNotHasKey('iOS requests', $browser->viewData('metrics'));
        $this->assertDatabaseCount('activity_visits', 1);
    }

    public function test_ios_history_paginates_twelve_requests_and_preserves_filters_without_needing_a_history_toggle()
    {
        $this->travelTo(carbon('2026-10-13 22:58:00'));
        $user = $this->account();
        for ($i = 0; $i < 13; $i++) $this->event($user->id, '2026-10-13 12:00:00');
        $this->actingAs(create(Admin::class), 'admin');
        $filters = ['range' => '30', 'platform' => 'app', 'audience' => 'registered'];
        $first = $this->get(route('admin.analytics.users', $filters))->assertOk()->viewData('history');
        $second = $this->get(route('admin.analytics.users', $filters + ['history_page' => 2]))->assertOk()->viewData('history');
        $this->assertSame(12, $first->perPage()); $this->assertSame(13, $first->total());
        $this->assertCount(12, $first); $this->assertCount(1, $second);
        $this->assertCount(0, $first->pluck('id')->intersect($second->pluck('id')));
        $this->assertSame([1], $second->pluck('id')->all());
        parse_str(parse_url($first->url(2), PHP_URL_QUERY), $parameters);
        foreach ($filters as $name => $value) $this->assertSame($value, $parameters[$name]);
    }

    public function test_daily_ios_queries_are_bounded_for_short_year_and_multi_year_ranges()
    {
        $this->travelTo(carbon('2026-10-13 22:58:00'));
        foreach (['app', 'all', 'webapp'] as $platform) foreach ([['2026-10-07', '2026-10-13', 7], ['2026-01-01', '2027-01-01', 366], ['2024-01-01', '2026-01-01', 732]] as [$from, $to, $count]) {
            $filters = $this->filters(['range' => 'custom'] + compact('platform', 'from', 'to'));
            DB::flushQueryLog(); DB::enableQueryLog();
            $trend = (new Queries)->trend($filters);
            $queries = count(DB::getQueryLog()); DB::disableQueryLog();
            $this->assertSame($platform === 'webapp' ? 3 : 4, $queries);
            $this->assertCount($count, $trend);
        }
    }
}
