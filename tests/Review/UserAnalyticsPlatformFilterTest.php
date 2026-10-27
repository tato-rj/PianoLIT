<?php

namespace Tests\Review;

use App\{Admin, User};
use App\Activity\Filters;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserAnalyticsPlatformFilterTest extends ReviewTestCase
{
    private function account()
    {
        return Model::withoutEvents(function () { return create(User::class); });
    }

    private function activity($user, $platform)
    {
        $visit = $platform === 'app' ? null : (string) Str::uuid();
        if ($visit) DB::table('activity_visits')->insert([
            'id' => $visit, 'user_id' => $user->id, 'visitor_id' => (string) Str::uuid(), 'platform' => $platform,
            'started_at' => '2026-10-19 09:00:00', 'last_activity_at' => '2026-10-19 09:01:00',
        ]);
        DB::table('activity_events')->insert([
            'event_id' => (string) Str::uuid(), 'visit_id' => $visit, 'user_id' => $user->id, 'platform' => $platform,
            'type' => $visit ? 'page_view' : 'request', 'source' => 'server', 'route_name' => $platform.'.discover',
            'occurred_at' => '2026-10-19 09:00:00', 'received_at' => now(), 'metadata' => '{}',
        ]);
    }

    public function test_app_platform_combinations_scope_visits_requests_and_daily_counts()
    {
        $this->travelTo(carbon('2026-10-19 10:50:00'));
        $user = $this->account();
        foreach (['web', 'webapp', 'app'] as $platform) $this->activity($user, $platform);
        $this->actingAs(create(Admin::class), 'admin');
        foreach ([['webapp'], ['app'], ['webapp', 'app']] as $platforms) {
            $parameters = ['platform' => array_merge([''], $platforms), 'range' => 'today', 'history' => 1];
            $response = $this->get(route('admin.analytics.users', $parameters))->assertOk();
            $expectedVisits = count(array_intersect(['webapp'], $platforms));
            $hasApp = in_array('app', $platforms, true);
            $this->assertSame($expectedVisits, $response->viewData('visits')->total());
            $this->assertSame($hasApp ? 1 : 0, $response->viewData('history')->total());
            $this->assertSame($expectedVisits, array_sum(array_column($response->viewData('trend'), 'total')));
            $this->assertSame($hasApp ? 1 : 0, array_sum(array_column($response->viewData('trend'), 'ios_requests')));
            $this->assertSame($platforms === ['app'], $response->viewData('iosOnly'));
            $this->assertSame($hasApp, array_key_exists('ios_requests', $response->viewData('series')));
            $this->assertSame($platforms, $response->viewData('filters')->platforms);
        }
        $this->assertDatabaseCount('activity_visits', 2);
        $this->assertDatabaseCount('activity_visit_links', 0);
    }

    public function test_shared_checkbox_menu_and_mixed_filter_work_on_insights_and_owned_profile_history()
    {
        $this->travelTo(carbon('2026-10-19 10:50:00'));
        $user = $this->account(); $other = $this->account();
        foreach ([$user, $other] as $account) foreach (['web', 'webapp', 'app'] as $platform) $this->activity($account, $platform);
        $this->actingAs(create(Admin::class), 'admin');
        $parameters = ['platform' => ['', 'webapp', 'app'], 'range' => 'today'];
        foreach ([route('admin.analytics.users'), route('admin.analytics.users.insights'), route('admin.analytics.users.profile', ['user' => $user->id, 'tab' => 'visits'])] as $url) {
            $response = $this->get($url.'?'.http_build_query($parameters))->assertOk()->assertSee('All platforms');
            $document = new \DOMDocument; @$document->loadHTML($response->getContent()); $xpath = new \DOMXPath($document);
            $this->assertSame(0, $xpath->query('//select[@name="platform"]')->length);
            $this->assertSame(2, $xpath->query('//input[@type="checkbox" and @name="platform[]"]')->length);
            $checked = $xpath->query('//input[@type="checkbox" and @name="platform[]" and @checked]');
            $this->assertSame(['webapp', 'app'], array_map(function ($node) { return $node->getAttribute('value'); }, iterator_to_array($checked)));
        }
        $insights = $this->get(route('admin.analytics.users.insights', $parameters))->assertOk();
        $this->assertArrayNotHasKey('pageViews', $insights->original->getData());
        $insights->assertDontSee('Page views by hub')->assertSee('Website & hub traffic in Google Analytics', false);
        $profile = $this->get(route('admin.analytics.users.profile', ['user' => $user->id, 'tab' => 'visits'] + $parameters))->assertOk();
        $this->assertSame(1, $profile->viewData('visits')->total());
        $this->assertSame(1, $profile->viewData('history')->total());
        $this->assertSame(['webapp'], $profile->viewData('visits')->pluck('platform')->all());
        $this->assertSame(['app'], $profile->viewData('history')->pluck('platform')->all());
    }

    public function test_old_scalar_links_remain_valid_and_empty_unknown_or_malformed_selections_are_rejected()
    {
        foreach (['all' => ['webapp', 'app'], 'web' => ['webapp'], 'webapp' => ['webapp'], 'app' => ['app']] as $old => $expected) {
            $filters = new Filters(Request::create('/', 'GET', ['platform' => $old]));
            $this->assertSame($old === 'web' ? 'webapp' : $old, $filters->values['platform']);
            $this->assertSame($expected, $filters->platforms);
        }
        $this->actingAs(create(Admin::class), 'admin')->withExceptionHandling();
        foreach ([[''], ['unknown'], ['app', 'app'], [['app']], ['all'], ['', 'web', 'webapp', 'app', 'app'], 'webapp,app'] as $invalid) {
            $this->getJson(route('admin.analytics.users', ['platform' => $invalid]))->assertStatus(422)->assertJsonStructure(['errors']);
        }
    }

    public function test_mixed_selection_survives_independent_visit_and_history_pagination_with_dates_and_sorting()
    {
        $this->travelTo(carbon('2026-10-19 10:50:00'));
        $user = $this->account();
        for ($i = 0; $i < 13; $i++) foreach (['webapp', 'app'] as $platform) $this->activity($user, $platform);
        $this->actingAs(create(Admin::class), 'admin');
        $parameters = ['platform' => ['', 'webapp', 'app'], 'range' => 'custom', 'from' => '2026-10-19', 'to' => '2026-10-19', 'audience' => 'registered', 'visit_sort' => 'duration', 'visit_order' => 'asc', 'history' => 1];
        $response = $this->get(route('admin.analytics.users', $parameters))->assertOk();
        foreach (['visits', 'history'] as $key) {
            $paginator = $response->viewData($key);
            $this->assertSame(13, $paginator->total()); $this->assertCount(12, $paginator);
            parse_str(parse_url($paginator->url(2), PHP_URL_QUERY), $query);
            $this->assertSame(['webapp', 'app'], array_values(array_filter($query['platform'])));
            foreach (array_diff_key($parameters, ['platform' => true]) as $name => $value) $this->assertEquals($value, $query[$name]);
            $next = $this->get($paginator->url(2))->assertOk()->viewData($key);
            $this->assertCount(1, $next);
            $this->assertCount(0, $paginator->pluck('id')->intersect($next->pluck('id')));
        }
    }
}
