<?php

namespace Tests\Review;

use App\{Admin, User};
use App\Activity\{Filters, Queries};
use App\Support\AdminDateRanges;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminDateRangeTest extends ReviewTestCase
{
    public function test_calendar_presets_handle_month_boundaries_leap_years_and_inclusive_end_dates()
    {
        foreach ([
            ['2026-10-13', 'this_month', '2026-10-01', '2026-10-13'],
            ['2026-10-13', 'last_month', '2026-09-01', '2026-09-30'],
            ['2026-01-02', 'last_month', '2025-12-01', '2025-12-31'],
            ['2024-03-31', 'last_month', '2024-02-01', '2024-02-29'],
            ['2026-11-02', '7', '2026-10-27', '2026-11-02'],
            ['2026-10-13', 'today', '2026-10-13', '2026-10-13'],
            ['2026-10-13', 'yesterday', '2026-10-12', '2026-10-12'],
        ] as [$now, $range, $from, $to]) {
            $this->travelTo(carbon($now.' 12:00:00'));
            $filters = new Filters(Request::create('/', 'GET', ['range' => $range]));
            $this->assertSame($from, $filters->start->toDateString());
            $this->assertSame(carbon($to)->addDay()->toDateString(), $filters->end->toDateString());
            $preset = AdminDateRanges::presets()[$range];
            $this->assertSame($from, $preset['from']);
            $this->assertSame($to, $preset['to']);
            $this->assertSame('registered', $filters->values['audience']);
        }
    }

    public function test_the_shared_popover_renders_on_activity_insights_and_account_visits()
    {
        $this->travelTo(carbon('2026-10-13 12:00:00'));
        $user = Model::withoutEvents(function () { return create(User::class); });
        $this->actingAs(create(Admin::class), 'admin');
        foreach ([route('admin.analytics.users'), route('admin.analytics.users.insights'), route('admin.analytics.users.profile', ['user' => $user->id, 'tab' => 'visits'])] as $url) {
            $response = $this->get($url)->assertOk()->assertSee('This month')->assertSee('Last month')->assertSee('All history')->assertDontSee('Last 30 days')->assertDontSee('Select “Custom dates”');
            $document = new \DOMDocument; @$document->loadHTML($response->getContent()); $xpath = new \DOMXPath($document);
            $widget = $xpath->query('//details[@data-admin-date-range]')->item(0);
            $this->assertNotNull($widget);
            $this->assertSame('range', $widget->getAttribute('data-period-name'));
            $this->assertFalse($widget->hasAttribute('data-max-days'));
            $this->assertSame(7, $xpath->query('.//button[@data-date-preset]', $widget)->length);
            $this->assertSame(['Today', 'Yesterday', 'Last 7 days', 'This month', 'Last month', 'All history', 'Custom range'], array_map(function ($button) { return trim($button->textContent); }, iterator_to_array($xpath->query('.//button[@data-date-preset]', $widget))));
            $this->assertSame(1, $xpath->query('.//input[@type="hidden" and @name="from"]', $widget)->length);
            $this->assertSame(1, $xpath->query('.//input[@type="hidden" and @name="to"]', $widget)->length);
            $this->assertSame(0, $xpath->query('.//input[@type="date" and @name]', $widget)->length, 'Draft edits do not become submitted filters until applied.');
            $this->assertSame('Custom range', $xpath->query('.//h3', $widget)->item(0)->textContent, 'An audience label must not leak into the component title.');
        }
        $this->get(route('admin.analytics.users', ['range' => 'custom', 'from' => '2026-10-01', 'to' => '2026-10-12', 'audience' => 'anonymous', 'platform' => 'webapp', 'visit_sort' => 'actions', 'visit_order' => 'asc']))
            ->assertOk()->assertSee('value="2026-10-01"', false)->assertSee('value="2026-10-12"', false)
            ->assertViewHas('visitSort', function ($sort) { return $sort->field === 'actions' && $sort->order === 'asc'; })
            ->assertViewHas('filters', function ($filters) { return $filters->values['audience'] === 'anonymous' && $filters->values['platform'] === 'webapp'; });
    }

    public function test_people_date_pairs_remain_independent_and_accept_open_ended_or_long_ranges()
    {
        $this->travelTo(carbon('2026-10-13 12:00:00'));
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.analytics.users.people', ['registered_to' => '2026-10-13', 'active_from' => '2018-01-01', 'active_to' => '2026-10-12']))->assertOk();
        $document = new \DOMDocument; @$document->loadHTML($response->getContent()); $xpath = new \DOMXPath($document);
        $widgets = $xpath->query('//details[@data-admin-date-range]');
        $this->assertSame(2, $widgets->length);
        $this->assertSame('Registration dates', $widgets[0]->getAttribute('data-label'));
        $this->assertSame('Activity dates', $widgets[1]->getAttribute('data-label'));
        $ids = [];
        foreach ($widgets as $widget) {
            $this->assertSame('false', $widget->getAttribute('data-required'));
            $this->assertSame('', $widget->getAttribute('data-max-days'));
            $ids[] = $xpath->query('.//div[@role="dialog"]', $widget)->item(0)->getAttribute('id');
        }
        $this->assertCount(2, array_unique($ids));
        $this->assertSame('', $xpath->query('//input[@name="registered_from"]')->item(0)->getAttribute('value'));
        $this->assertSame('2026-10-13', $xpath->query('//input[@name="registered_to"]')->item(0)->getAttribute('value'));
        $this->assertSame('2018-01-01', $xpath->query('//input[@name="active_from"]')->item(0)->getAttribute('value'));
    }

    public function test_custom_bounds_and_invalid_presets_are_still_validated_on_the_server()
    {
        $this->actingAs(create(Admin::class), 'admin')->withExceptionHandling();
        foreach ([
            ['range' => 'custom'], ['range' => 'custom', 'from' => '2026-10-13', 'to' => '2026-10-12'],
            ['range' => 'custom', 'from' => '2026-02-29', 'to' => '2026-03-01'],
            ['range' => ['this_month']], ['range' => 'unknown'],
        ] as $values) $this->getJson(route('admin.analytics.users', $values))->assertStatus(422);
        foreach ([['2026-01-01', '2027-01-02'], ['2018-01-01', '2026-10-13']] as [$from, $to]) {
            $filters = new Filters(Request::create('/', 'GET', ['range' => 'custom'] + compact('from', 'to')));
            $this->assertSame($from, $filters->start->toDateString());
            $this->assertSame(carbon($to)->addDay()->toDateString(), $filters->end->toDateString());
        }
        $this->get(route('admin.analytics.users', ['range' => 'custom', 'from' => '2024-01-01', 'to' => '2026-01-01']))->assertOk();
    }

    public function test_all_history_includes_old_dated_browser_and_app_records_with_existing_scopes()
    {
        $this->travelTo(carbon('2026-10-13 12:00:00'));
        $user = Model::withoutEvents(function () { return create(User::class); });
        $excluded = Model::withoutEvents(function () { return create(User::class, ['email' => 'elena.dare@gmail.com']); });
        $oldVisit = (string) Str::uuid();
        foreach ([[$oldVisit, $user->id, '2024-10-12 09:00:00'], [(string) Str::uuid(), $user->id, '2026-10-13 09:00:00'], [(string) Str::uuid(), null, '2020-01-01 09:00:00'], [(string) Str::uuid(), $excluded->id, '2010-01-01 09:00:00']] as [$id, $userId, $at]) {
            DB::table('activity_visits')->insert(['id' => $id, 'user_id' => $userId, 'visitor_id' => (string) Str::uuid(), 'platform' => 'webapp', 'started_at' => $at, 'last_activity_at' => $at]);
        }
        foreach ([['2022-02-10 12:00:00', 'legacy_request'], [null, 'legacy_unparsed'], ['2026-10-14 00:00:00', 'request']] as [$at, $type]) {
            DB::table('activity_events')->insert(['event_id' => (string) Str::uuid(), 'user_id' => $user->id, 'platform' => 'app', 'type' => $type, 'source' => 'redis', 'occurred_at' => $at, 'received_at' => now(), 'metadata' => '{}']);
        }
        foreach ([['registration_started', 'browser', '2024-10-12 09:00:00'], ['trial_started', 'server', '2026-10-13 09:00:00']] as [$type, $source, $at]) {
            DB::table('activity_events')->insert(['event_id' => (string) Str::uuid(), 'visit_id' => $oldVisit, 'user_id' => $user->id, 'platform' => 'webapp', 'type' => $type, 'source' => $source, 'occurred_at' => $at, 'received_at' => now(), 'metadata' => '{}']);
        }
        $this->actingAs(create(Admin::class), 'admin');
        $parameters = ['range' => 'all', 'history' => 1, 'visit_sort' => 'date', 'visit_order' => 'asc'];
        $response = $this->get(route('admin.analytics.users', $parameters))->assertOk()->assertDontSee('Last 30 days');
        $this->assertNull($response->viewData('filters')->start);
        $this->assertSame(2, $response->viewData('metrics')['Visits']);
        $this->assertSame(1, $response->viewData('metrics')['iOS requests']);
        $this->assertSame(1, $response->viewData('history')->total(), 'Undated imports and future records do not enter dated history.');
        $this->assertSame($oldVisit, $response->viewData('visits')->first()->id);
        $trend = $response->viewData('trend');
        $this->assertSame('2022-02-10', $trend[0]['day']);
        $this->assertSame('2026-10-13', end($trend)['day']);
        $this->assertSame(2, array_sum(array_column($trend, 'total')));
        $this->assertSame(1, array_sum(array_column($trend, 'ios_requests')));
        $this->assertStringContainsString('range=all', $response->viewData('visits')->url(2));
        $browser = (new Queries)->trend(new Filters(Request::create('/', 'GET', ['range' => 'all', 'platform' => 'webapp'])));
        $this->assertSame('2024-10-12', $browser[0]['day'], 'Other platforms, anonymous visits and excluded accounts do not extend the chart.');
        $profile = $this->get(route('admin.analytics.users.profile', ['user' => $user->id, 'tab' => 'visits'] + $parameters))->assertOk();
        $this->assertSame(2, $profile->viewData('visits')->total());
        $this->assertSame(1, $profile->viewData('history')->total());
        $insights = $this->get(route('admin.analytics.users.insights', ['range' => 'all']))->assertOk();
        $this->assertSame(1, collect($insights->viewData('conversions'))->firstWhere('from', 'registration_started')['started']);
    }

    public function test_all_history_without_records_renders_a_zero_day_and_old_thirty_day_links_still_work()
    {
        $this->travelTo(carbon('2026-10-13 12:00:00'));
        $filters = new Filters(Request::create('/', 'GET', ['range' => 'all', 'from' => '2026-10-01', 'to' => '2026-10-02']));
        $this->assertArrayNotHasKey('from', $filters->values);
        $this->assertArrayNotHasKey('to', $filters->values);
        $this->assertSame([['day' => '2026-10-13', 'date' => 'Oct 13', 'total' => 0, 'pieces' => 0, 'media_starts' => 0, 'ios_requests' => 0]], (new Queries)->trend($filters));
        $this->actingAs(create(Admin::class), 'admin');
        $this->get(route('admin.analytics.users', ['range' => 'all']))->assertOk()->assertSee('All history')->assertDontSee('NAN');
        $response = $this->get(route('admin.analytics.users', ['range' => '30']))->assertOk()->assertDontSee('Last 30 days');
        $this->assertSame('custom', $response->viewData('filters')->values['range']);
        $this->assertSame('2026-09-14', $response->viewData('filters')->start->toDateString());
    }
}
