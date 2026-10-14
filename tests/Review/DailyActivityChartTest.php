<?php

namespace Tests\Review;

use App\{Admin, User};
use App\Activity\{Filters, Queries};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DailyActivityChartTest extends ReviewTestCase
{
    private function visit($userId, $at, $platform = 'webapp')
    {
        $id = (string) Str::uuid();
        DB::table('activity_visits')->insert(['id' => $id, 'user_id' => $userId, 'visitor_id' => (string) Str::uuid(), 'platform' => $platform, 'started_at' => $at, 'last_activity_at' => carbon($at)->addMinutes(8)]);
        return $id;
    }

    private function event($visitId, $type, $at, array $extra = [])
    {
        $visit = $visitId ? DB::table('activity_visits')->where('id', $visitId)->first() : null;
        DB::table('activity_events')->insert(array_merge(['event_id' => (string) Str::uuid(), 'visit_id' => $visitId,
            'platform' => $visit->platform ?? 'app', 'type' => $type, 'source' => $type === 'page_view' ? 'server' : 'browser',
            'occurred_at' => $at, 'received_at' => '2026-10-13 12:00:00', 'metadata' => '{}'], $extra));
    }

    private function filters(array $values = [])
    {
        return new Filters(Request::create('/', 'GET', array_merge(['range' => 'custom', 'from' => '2026-10-11', 'to' => '2026-10-13', 'platform' => 'webapp'], $values)));
    }

    public function test_daily_series_match_the_metric_totals_and_keep_visit_and_event_dates_separate()
    {
        $user = Model::withoutEvents(function () { return create(User::class); });
        $visit = $this->visit($user->id, '2026-10-11 23:50:00');
        $linked = $this->visit(null, '2026-10-11 14:00:00');
        DB::table('activity_visit_links')->insert(['visit_id' => $linked, 'user_id' => $user->id, 'reason' => 'login', 'linked_at_utc' => '2026-10-11 18:01:00']);
        $this->visit($user->id, '2026-10-12 08:00:00');
        $piece = ['resource_type' => 'piece', 'resource_id' => '1178'];
        $this->event($visit, 'page_view', '2026-10-11 23:50:00', $piece);
        $this->event($visit, 'media_play', '2026-10-11 23:59:59');
        $this->event($visit, 'page_view', '2026-10-12 00:00:00', $piece);
        $this->event($visit, 'page_view', '2026-10-12 00:02:00', $piece);
        $this->event($linked, 'media_play', '2026-10-12 00:03:00');
        $this->event($visit, 'media_play', '2026-10-12 00:04:00');
        $this->event($visit, 'media_pause', '2026-10-12 00:05:00');
        $this->event($visit, 'media_progress', '2026-10-12 00:06:00');
        $this->event($visit, 'page_view', '2026-10-12 00:07:00', array_merge($piece, ['source' => 'browser']));
        $this->event($visit, 'page_view', '2026-10-12 00:08:00', ['resource_type' => 'piece']);
        $this->event($visit, 'page_view', '2026-10-12 00:09:00', ['resource_type' => 'playlist', 'resource_id' => '20']);
        $this->event($visit, 'media_play', null);
        $this->event($visit, 'media_play', '2026-10-10 23:59:59');
        $this->event($visit, 'media_play', '2026-10-14 00:00:00');
        $queries = new Queries; $filters = $this->filters();
        $trend = $queries->trend($filters);
        $this->assertSame([2, 1, 0], array_column($trend, 'total'));
        $this->assertSame([1, 2, 0], array_column($trend, 'pieces'));
        $this->assertSame([1, 2, 0], array_column($trend, 'media_starts'));
        $this->assertSame(['2026-10-11', '2026-10-12', '2026-10-13'], array_column($trend, 'day'));
        $metrics = $queries->metrics($filters);
        foreach (['total' => 'Visits', 'pieces' => 'Pieces opened', 'media_starts' => 'Media starts'] as $key => $label) {
            $this->assertSame((int) $metrics[$label], array_sum(array_column($trend, $key)));
        }
    }

    public function test_series_exclude_other_platforms_audiences_old_visits_and_ungrouped_history()
    {
        $user = Model::withoutEvents(function () { return create(User::class); });
        foreach ([$this->visit($user->id, '2026-10-10 23:59:59'), $this->visit($user->id, '2026-10-11 12:00:00', 'web'), $this->visit(null, '2026-10-11 12:00:00'), null] as $visit) {
            $this->event($visit, 'page_view', '2026-10-12 12:00:00', ['resource_type' => 'piece', 'resource_id' => '1178']);
            $this->event($visit, 'media_play', '2026-10-12 12:00:01');
        }
        $queries = new Queries;
        $trend = $queries->trend($this->filters());
        foreach (['total', 'pieces', 'media_starts'] as $key) $this->assertSame([0, 0, 0], array_column($trend, $key));
        $anonymous = $queries->trend($this->filters(['audience' => 'anonymous']));
        $this->assertSame([1, 0, 0], array_column($anonymous, 'total'));
        $this->assertSame([0, 1, 0], array_column($anonymous, 'pieces'));
        $this->assertSame([0, 1, 0], array_column($anonymous, 'media_starts'));
        $all = $queries->trend($this->filters(['audience' => 'all', 'platform' => 'all']));
        $this->assertSame(2, array_sum(array_column($all, 'total')));
        $this->assertSame(2, array_sum(array_column($all, 'pieces')));
        $this->assertSame(2, array_sum(array_column($all, 'media_starts')));
    }

    public function test_chart_shares_one_scale_and_renders_exact_values_for_all_series_including_zero_days()
    {
        $trend = [['day' => '2026-10-12', 'date' => 'Oct 12', 'total' => 7, 'pieces' => 23, 'media_starts' => 19],
            ['day' => '2026-10-13', 'date' => 'Oct 13', 'total' => 0, 'pieces' => 0, 'media_starts' => 0]];
        $html = view('admin.pages.analytics.users.daily-activity-chart', compact('trend'))->render();
        $document = new \DOMDocument; @$document->loadHTML($html); $xpath = new \DOMXPath($document);
        $bars = $xpath->query('//div[contains(@class,"ua-chart-bar ")]');
        $this->assertCount(6, $bars);
        foreach ([7, 23, 19, 0, 0, 0] as $index => $value) $this->assertSame('height:'.($value / 24 * 100).'%', $bars[$index]->getAttribute('style'));
        $this->assertStringContainsString('Pieces opened: 23, Media starts: 19', $html);
        $this->assertStringContainsString('>Count</div>', $html);
        $this->assertStringContainsString('>Date</div>', $html);
        $this->assertStringContainsString('>24</span>', $html);
        $this->assertStringContainsString('ua-chart-series--visits', $html);
        $this->assertStringContainsString('ua-chart-series--pieces', $html);
        $this->assertStringContainsString('ua-chart-series--media', $html);
        $zero = view('admin.pages.analytics.users.daily-activity-chart', ['trend' => [$trend[1]]])->render();
        $this->assertStringContainsString('>4</span>', $zero);
        $this->assertStringNotContainsString('NAN', $zero);
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.analytics.users'))->assertOk()->assertSee('Daily activity')->assertSee('Chart series')->assertSee('Daily counts and metric definitions');
        $this->assertSame(4, substr_count($response->getContent(), 'class="ua-chart-swatch '));
    }

    public function test_daily_queries_remain_constant_for_week_and_full_year_ranges()
    {
        $queries = new Queries;
        foreach ([['2026-10-07', '2026-10-13', 7], ['2026-01-01', '2027-01-01', 366]] as [$from, $to, $count]) {
            $filters = $this->filters(compact('from', 'to'));
            DB::flushQueryLog(); DB::enableQueryLog();
            $trend = $queries->trend($filters);
            $queryCount = count(DB::getQueryLog()); DB::disableQueryLog();
            $this->assertSame(3, $queryCount, 'Daily series use grouped queries, not one query per date.');
            $this->assertCount($count, $trend);
        }
    }
}
