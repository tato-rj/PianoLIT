<?php

namespace Tests\Review;

use App\{Admin, User};
use App\Activity\{Filters, Queries, Recorder};
use App\Analytics\WebsiteTracking;
use App\Http\Middleware\Logs\RecordActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Route};

class WebsiteAnalyticsSplitTest extends GamesTestCase
{
    protected function prepareUrlForRequest($uri)
    {
        return strpos($uri, 'http') === 0 ? $uri : parent::prepareUrlForRequest($uri);
    }

    protected function tearDown(): void
    {
        app()->instance('env', 'testing');
        parent::tearDown();
    }

    public function setUp(): void
    {
        parent::setUp();
        config(['activity.minimum_visit_seconds' => 0, 'activity.filter_bots' => false]);
        Route::middleware(['web', RecordActivity::class])->get('/review/analytics-split', function () {
            return response('<html>'.view('layouts.html.google.manager-head')->render()
                .view('layouts.html.google.manager-body')->render().view('components.tracking.activity')->render().'</html>');
        })->name('review.analytics-split');
    }

    public function test_all_public_hosts_load_google_but_only_my_collects_detailed_sql_journeys()
    {
        app()->instance('env', 'production');
        foreach (['', 'www.', 'learn.', 'blog.', 'shop.', 'games.', 'metronome.'] as $prefix) {
            $this->get('http://'.$prefix.'localhost/review/analytics-split?utm_source=review&utm_medium=test&utm_campaign=split')
                ->assertOk()->assertSee('GTM-KJW7XGP')->assertDontSee('pianoActivityConfig');
        }
        foreach (['activity_events', 'activity_pages', 'activity_visits', 'campaign_visits'] as $table) $this->assertDatabaseCount($table, 0);
        $this->get('http://my.localhost/review/analytics-split')->assertOk()->assertSee('G-12WWWZZMJF')->assertDontSee('GTM-KJW7XGP')->assertSee('pianoActivityConfig');
        $this->assertDatabaseCount('activity_pages', 1);
        $this->assertDatabaseHas('activity_events', ['platform' => 'webapp', 'type' => 'page_view']);
    }

    public function test_focused_games_metronome_and_guest_webapp_pages_include_the_shared_google_tag()
    {
        app()->instance('env', 'production');
        foreach (['games.note-nest.play', 'metronome.home', 'webapp.explore'] as $route) {
            $html = $this->get(route($route))->assertOk()->getContent();
            $this->assertSame(1, substr_count($html, 'gtag/js?id=G-12WWWZZMJF'), $route);
            $this->assertStringNotContainsString('GTM-KJW7XGP', $html, $route);
        }
    }

    public function test_admin_unknown_hosts_sensitive_links_and_excluded_accounts_are_not_sent_to_google()
    {
        app()->instance('env', 'production');
        foreach (['http://admin.localhost/', 'http://127.0.0.1/', 'http://other.example/', 'http://localhost/?signature=private'] as $url) {
            $this->assertFalse(WebsiteTracking::enabled(Request::create($url)));
        }
        $this->actingAs(\Illuminate\Database\Eloquent\Model::withoutEvents(function () { return create(User::class, ['email' => 'elena.dare@gmail.com']); }), 'web');
        $this->get('http://my.localhost/review/analytics-split')->assertOk()->assertDontSee('GTM-KJW7XGP')->assertDontSee('pianoActivityConfig');
    }

    public function test_old_public_browser_batches_are_acknowledged_without_creating_activity()
    {
        $this->postJson('http://localhost/activity/events', ['page_token' => 'old-public-token', 'events' => []])->assertNoContent();
        $this->assertDatabaseCount('activity_events', 0);
        $this->assertDatabaseCount('activity_visits', 0);
    }

    public function test_admin_sends_acquisition_to_google_and_does_not_offer_website_as_an_app_platform()
    {
        $this->actingAs(create(Admin::class), 'admin');
        $this->get(route('admin.analytics.campaigns'))->assertRedirect('https://analytics.google.com/analytics/web/#/a137232556p399721623/reports/reportinghub');
        $this->get(route('admin.analytics.users.insights'))->assertOk()->assertSee('Website & hub traffic in Google Analytics', false)
            ->assertDontSee('Visits by signup month')->assertDontSee('Page views by hub')->assertDontSee('value="web"', false);
        $this->assertSame(['webapp', 'app'], (new Filters(Request::create('/')))->platforms);
    }
}
