<?php

namespace Tests\Review;

use App\{Admin, User};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminPaginationTest extends ReviewTestCase
{
    public function test_twelve_row_lists_preserve_filters_and_keep_independent_page_boundaries()
    {
        $this->travelTo(carbon('2026-10-13 12:00:00'));
        $user = Model::withoutEvents(function () { return create(User::class); });
        $this->actingAs(create(Admin::class), 'admin');
        $visitIds = [];
        for ($i = 0; $i < 13; $i++) {
            $id = (string) Str::uuid(); $visitIds[] = $id;
            $at = carbon('2026-10-13 09:00:00')->addMinutes($i);
            DB::table('activity_visits')->insert(['id' => $id, 'user_id' => $user->id, 'visitor_id' => (string) Str::uuid(), 'platform' => 'webapp', 'started_at' => $at, 'last_activity_at' => $at->copy()->addMinutes(8)]);
            DB::table('activity_events')->insert(['event_id' => (string) Str::uuid(), 'user_id' => $user->id, 'platform' => 'webapp', 'type' => 'login', 'source' => 'server', 'occurred_at' => $at, 'received_at' => $at, 'metadata' => '{}']);
        }
        $filters = ['range' => 'today', 'platform' => 'webapp', 'audience' => 'registered', 'visit_sort' => 'duration', 'visit_order' => 'asc', 'history' => 1];
        $first = $this->get(route('admin.analytics.users', $filters))->assertOk();
        $second = $this->get(route('admin.analytics.users', $filters + ['visits_page' => 2, 'history_page' => 2]))->assertOk();
        foreach (['visits', 'history'] as $key) {
            $page = $first->viewData($key); $next = $second->viewData($key);
            $this->assertSame(12, $page->perPage());
            $this->assertCount(12, $page); $this->assertCount(1, $next);
            $this->assertSame(13, $page->total());
            $this->assertCount(0, $page->pluck('id')->intersect($next->pluck('id')));
            parse_str(parse_url($next->url(1), PHP_URL_QUERY), $query);
            foreach ($filters as $name => $value) $this->assertSame((string) $value, $query[$name]);
        }
        $this->assertSame([$visitIds[0]], $second->viewData('visits')->pluck('id')->all(), 'Duration ties retain the existing newest-first fallback.');
        foreach (['overview', 'visits', 'music', 'account'] as $tab) {
            $response = $this->get(route('admin.analytics.users.profile', ['user' => $user->id, 'tab' => $tab]))->assertOk();
            $this->assertSame(12, $response->viewData('visits')->perPage());
            if ($tab === 'music') foreach ($response->viewData('music') as $list) $this->assertSame(12, $list->perPage());
            if ($tab === 'account') {
                $this->assertSame(12, $response->viewData('accountEvents')->perPage());
                $this->assertCount(12, $response->viewData('accountEvents'));
            }
        }
        $activity = $this->get(route('admin.activity.index'))->assertOk();
        foreach (['visits', 'legacy'] as $key) {
            $this->assertSame(12, $activity->viewData($key)->perPage());
            $this->assertCount(12, $activity->viewData($key));
        }
        Model::withoutEvents(function () { create(User::class, [], 12); });
        $people = $this->get(route('admin.analytics.users.people'))->assertOk()->viewData('people');
        $this->assertSame(12, $people->perPage()); $this->assertCount(12, $people);
        $this->assertCount(1, $this->get(route('admin.analytics.users.people', ['page' => 2]))->assertOk()->viewData('people'));
    }

    public function test_journey_pages_and_unassociated_actions_each_paginate_twelve_independently()
    {
        $this->actingAs(create(Admin::class), 'admin');
        $id = (string) Str::uuid(); $visitor = (string) Str::uuid();
        DB::table('activity_visits')->insert(['id' => $id, 'visitor_id' => $visitor, 'platform' => 'webapp', 'started_at' => '2026-10-13 09:00:00', 'last_activity_at' => '2026-10-13 09:13:00']);
        for ($i = 0; $i < 13; $i++) {
            $at = carbon('2026-10-13 09:00:00')->addMinutes($i);
            DB::table('activity_pages')->insert(['id' => (string) Str::uuid(), 'visit_id' => $id, 'visitor_id' => $visitor, 'platform' => 'webapp', 'route_name' => 'webapp.discover', 'url' => 'https://my.pianolit.test/', 'opened_at' => $at]);
            DB::table('activity_events')->insert(['event_id' => (string) Str::uuid(), 'visit_id' => $id, 'visitor_id' => $visitor, 'platform' => 'webapp', 'type' => 'click', 'source' => 'browser', 'occurred_at' => $at, 'received_at' => $at, 'metadata' => '{}']);
        }
        $first = $this->get(route('admin.analytics.users.journey', $id))->assertOk();
        $second = $this->get(route('admin.analytics.users.journey', ['visit' => $id, 'pages_page' => 2, 'events_page' => 2]))->assertOk();
        foreach (['pages' => 'pages_page', 'unplaced' => 'events_page'] as $key => $parameter) {
            $page = $first->viewData($key); $next = $second->viewData($key);
            $this->assertSame($parameter, $page->getPageName());
            $this->assertSame(12, $page->perPage());
            $this->assertSame(13, $page->total());
            $this->assertCount(12, $page); $this->assertCount(1, $next);
            $this->assertCount(0, $page->pluck('id')->intersect($next->pluck('id')));
        }
        $this->assertSame(13, $second->viewData('visit')->pages_count);
    }
}
