<?php

namespace Tests\Review;

use App\{Admin, User};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{DB, Redis};
use Illuminate\Support\Str;

class UserAnalyticsHistoryTest extends ReviewTestCase
{
    private function account(array $attributes = [])
    {
        return Model::withoutEvents(function () use ($attributes) { return create(User::class, $attributes)->setAppends(['full_name']); });
    }

    private function event($user, $at, $platform = 'app', $url = 'https://pianolit.com/api/discover')
    {
        return DB::table('activity_events')->insertGetId([
            'event_id' => (string) Str::uuid(), 'user_id' => $user->id, 'platform' => $platform,
            'type' => 'legacy_request', 'source' => 'redis', 'occurred_at' => $at,
            'received_at' => '2026-10-12 12:00:00', 'url' => $url, 'metadata' => '{}',
        ]);
    }

    private function url($user, array $filters = [])
    {
        return route('admin.analytics.users.profile', ['user' => $user->id, 'tab' => 'visits'] + $filters);
    }

    public function test_imported_app_logs_appear_in_the_same_accounts_new_history_without_fabricating_visits()
    {
        $user = $this->account(['first_name' => 'Maria', 'last_name' => 'Rossi']);
        $redis = \Mockery::mock();
        Redis::shouldReceive('connection')->with('default')->andReturn($redis);
        $prefix = config('database.redis.prefix');
        $key = $prefix.'user:'.$user->id.':app';
        $timestamp = (string) carbon('2022-02-10 11:32:00')->timestamp;
        $payload = '{"url":"https://pianolit.com/api/pieces/find","data":{"piece_id":42}}';
        $redis->shouldReceive('scan')->with('0', 'MATCH', $prefix.'*', 'COUNT', 500)->once()->andReturn(['0', [$key]]);
        $redis->shouldReceive('scan')->with('0', 'MATCH', $prefix.'user:*', 'COUNT', 500)->once()->andReturn(['0', [$key]]);
        $redis->shouldReceive('type')->with($key)->once()->andReturn('hash');
        $redis->shouldReceive('hscan')->with($key, '0', 'COUNT', 500)->twice()->andReturn(['0', [$timestamp => $payload]]);
        $this->artisan('activity:import-redis', ['--before' => (string) (time() - 60), '--sleep-ms' => 0, '--verify' => true])->assertExitCode(0);

        $this->actingAs(create(Admin::class), 'admin');
        $this->get(route('admin.users.show', $user->id))->assertOk()->assertDontSee('Feb 10, 2022')->assertSee('Open logs & analytics', false);
        $new = $this->get($this->url($user, ['range' => 'custom', 'from' => '2022-02-01', 'to' => '2022-02-28']))->assertOk()
            ->assertSee('Ungrouped activity')->assertSee('Feb 10, 2022 · 11:32:00 AM')->assertSee('https://pianolit.com/api/pieces/find')
            ->assertSee('1 record in this period')->assertSee('Legacy account associations may be unverified.');
        $this->assertSame(DB::table('activity_events')->where('user_id', $user->id)->orderByDesc('id')->pluck('id')->all(), $new->viewData('history')->pluck('id')->all());
        $this->assertSame(0, $new->viewData('visits')->total());
        $this->assertSame(0, (int) $new->viewData('stats')->visits);
        $this->assertDatabaseCount('activity_visits', 0);
        $this->assertDatabaseCount('activity_pages', 0);
        $this->assertDatabaseHas('activity_events', ['legacy_payload' => $payload, 'visit_id' => null, 'user_id' => $user->id]);
    }

    public function test_empty_date_range_identifies_stored_history_and_links_to_its_actual_latest_day()
    {
        $user = $this->account();
        $this->event($user, '2022-02-09 16:18:00');
        $latest = $this->event($user, '2022-02-10 11:32:00');
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get($this->url($user, ['range' => 'custom', 'from' => '2022-03-01', 'to' => '2022-03-01', 'platform' => 'web']))->assertOk()
            ->assertSee('0 records in this period')->assertSee('2 records stored for this account.')
            ->assertSee('Latest dated activity: Feb 10, 2022.')->assertSee('No dated ungrouped activity in the selected period and platform.');
        $this->assertSame(0, $response->viewData('history')->total());
        $link = $response->viewData('ungroupedHistoryUrl');
        parse_str(parse_url($link, PHP_URL_QUERY), $parameters);
        $this->assertSame(['range' => 'custom', 'from' => '2022-02-10', 'to' => '2022-02-10', 'platform' => 'all'], $parameters);
        $this->assertSame('ungrouped-history', parse_url($link, PHP_URL_FRAGMENT));
        $this->assertSame([$latest], $this->get($link)->assertOk()->viewData('history')->pluck('id')->all());
        $this->assertNull($this->get(route('admin.analytics.users.profile', $user->id))->assertOk()->assertSee($link)->viewData('history'));
    }

    public function test_history_respects_account_platform_and_inclusive_dates_and_does_not_date_unknown_imports_by_receipt()
    {
        $user = $this->account(); $other = $this->account();
        $early = $this->event($user, '2022-02-09 00:00:00');
        $late = $this->event($user, '2022-02-10 23:59:59');
        $web = $this->event($user, '2022-02-10 12:00:00', 'web');
        $this->event($user, '2022-02-08 23:59:59');
        $this->event($user, '2022-02-11 00:00:00');
        $this->event($other, '2022-02-10 23:59:59', 'app', 'https://pianolit.com/api/private-other-account');
        $this->event($user, null);
        $visit = (string) Str::uuid();
        DB::table('activity_visits')->insert(['id' => $visit, 'user_id' => $user->id, 'visitor_id' => (string) Str::uuid(), 'platform' => 'app', 'started_at' => '2022-02-10 12:00:00', 'last_activity_at' => '2022-02-10 12:01:00']);
        $grouped = $this->event($user, '2022-02-10 12:00:00');
        DB::table('activity_events')->where('id', $grouped)->update(['visit_id' => $visit]);

        $this->actingAs(create(Admin::class), 'admin');
        $filters = ['range' => 'custom', 'from' => '2022-02-09', 'to' => '2022-02-10', 'platform' => 'app', 'user_id' => $other->id];
        $response = $this->get($this->url($user, $filters))->assertOk()->assertDontSee('private-other-account');
        $this->assertSame([$late, $early], $response->viewData('history')->pluck('id')->all());
        $this->assertSame(1, $response->viewData('visits')->total());
        $filters['platform'] = 'all';
        $this->assertSame([$late, $web, $early], $this->get($this->url($user, $filters))->assertOk()->viewData('history')->pluck('id')->all());
        $this->assertSame(0, $this->get($this->url($user, ['range' => 'custom', 'from' => '2026-10-12', 'to' => '2026-10-12']))->assertOk()->viewData('history')->total());
    }

    public function test_history_paginates_twelve_records_independently_and_preserves_filters_and_visit_sort()
    {
        $user = $this->account();
        for ($i = 0; $i < 13; $i++) $this->event($user, '2022-02-10 11:32:00');
        $this->actingAs(create(Admin::class), 'admin');
        $filters = ['range' => 'custom', 'from' => '2022-02-01', 'to' => '2022-02-28', 'platform' => 'app', 'visit_sort' => 'duration', 'visit_order' => 'asc', 'visits_page' => 2];
        $first = $this->get($this->url($user, $filters))->assertOk()->viewData('history');
        $second = $this->get($this->url($user, $filters + ['history_page' => 2]))->assertOk()->viewData('history');
        $this->assertSame('history_page', $first->getPageName());
        $this->assertSame(12, $first->perPage());
        $this->assertSame(13, $first->total());
        $this->assertCount(12, $first); $this->assertCount(1, $second);
        $this->assertCount(0, $first->pluck('id')->intersect($second->pluck('id')));
        $this->assertSame([1], $second->pluck('id')->all(), 'Equal times are ordered by descending event ID.');
        parse_str(parse_url($first->url(2), PHP_URL_QUERY), $parameters);
        foreach ($filters as $key => $value) $this->assertSame((string) $value, $parameters[$key]);
        $this->assertSame('ungrouped-history', parse_url($first->url(2), PHP_URL_FRAGMENT));
    }

    public function test_history_keeps_admin_authorization_exclusions_and_html_escaping()
    {
        $user = $this->account();
        $excluded = $this->account(['email' => ' Elena.Dare@Gmail.com ']);
        $this->event($user, '2022-02-10 11:32:00', 'app', 'https://pianolit.com/api/search?q=<script>alert(1)</script>');
        $this->event($excluded, '2022-02-10 11:32:00');
        $filters = ['range' => 'custom', 'from' => '2022-02-10', 'to' => '2022-02-10'];
        $this->withExceptionHandling()->actingAs($user, 'web');
        $this->get($this->url($user, $filters))->assertRedirect();
        $this->actingAs(create(Admin::class), 'admin');
        $this->get($this->url($excluded, $filters))->assertNotFound();
        $this->get($this->url($user, $filters))->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }
}
