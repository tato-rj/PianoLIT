<?php

namespace Tests\Review;

use App\{Admin, User};
use App\Activity\{Filters, Insights, PageIdentity, PageViews, Presentation};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PageViewAnalyticsTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->travelTo(carbon('2026-10-27 12:00:00'));
    }

    private function filters(array $values = [])
    {
        return new Filters(Request::create('/', 'GET', $values + ['range' => 'this_month', 'audience' => 'all']));
    }

    private function record($route, $url, array $event = [], array $visit = [])
    {
        $visit = $visit + ['id' => (string) Str::uuid(), 'visitor_id' => (string) Str::uuid(), 'user_id' => null,
            'platform' => 'web', 'started_at' => '2026-10-20 10:00:00', 'last_activity_at' => '2026-10-20 10:01:00'];
        DB::table('activity_visits')->insert($visit);
        return DB::table('activity_events')->insertGetId($event + ['event_id' => (string) Str::uuid(), 'visit_id' => $visit['id'],
            'user_id' => $visit['user_id'], 'visitor_id' => $visit['visitor_id'], 'platform' => $visit['platform'],
            'route_name' => $route, 'url' => $url, 'type' => 'page_view', 'source' => 'server', 'method' => 'GET', 'status' => 200,
            'occurred_at' => '2026-10-20 10:00:00', 'received_at' => '2026-10-20 10:00:01']);
    }

    public function test_historical_and_current_urls_share_content_hubs_without_losing_individual_quizzes()
    {
        Model::withoutEvents(function () { create(\App\Quiz\Quiz::class, ['slug' => 'musical-terms', 'title' => 'How well do you know musical terms?']); });
        foreach ([
            ['tools.chord-finder.index', 'https://pianolit.com/tools/chord-finder?notes=C,E,G'],
            ['tools.chord-finder.index', 'https://learn.pianolit.com/chord-finder#piano'],
            ['tools.chord-finder.index', 'https://learn.pianolit.com/chord-finder/?notes=D,F,A'],
            ['tools.circle-of-fifths', 'https://pianolit.com/tools/circle-of-fifths'],
            ['tools.circle-of-fifths', 'https://learn.pianolit.com/circle-of-fifths'],
            ['quizzes.index', 'https://pianolit.com/quizzes?page=2'],
            ['quizzes.show', 'https://pianolit.com/quizzes/musical-terms'],
            ['quizzes.show', 'https://learn.pianolit.com/quizzes/musical-terms?email=secret@example.com'],
            ['quizzes.show', 'https://learn.pianolit.com/quizzes/composers'],
            ['posts.show', 'https://pianolit.com/blog/piano-practice'],
            ['posts.show', 'https://blog.pianolit.com/piano-practice'],
            ['home', 'https://pianolit.com/'],
            ['games.home', 'https://games.pianolit.com/'],
            ['metronome.home', 'https://metronome.pianolit.com/'],
            ['ebooks.index', 'https://shop.pianolit.com/ebooks'],
        ] as [$route, $url]) $this->record($route, $url);
        $report = (new PageViews)->get($this->filters());
        $this->assertSame(15, $report['total']);
        $this->assertSame(15, $report['hubs']->sum('total'));
        $this->assertSame(9, $report['hubs']['learn']->total);
        $this->assertSame(2, $report['hubs']['blog']->total);
        $this->assertSame(['quizzes' => 4, 'chord-finder' => 3, 'circle-of-fifths' => 2], $report['focus']->pluck('total', 'key')->all());
        $pages = $report['pages']->getCollection()->keyBy('key');
        $this->assertSame(3, $pages['learn:/chord-finder']->total);
        $this->assertSame(2, $pages['learn:/quizzes/musical-terms']->total);
        $this->assertSame('Quiz · How well do you know musical terms?', $pages['learn:/quizzes/musical-terms']->name);
        $this->assertSame(1, $pages['learn:/quizzes/composers']->total);
        $this->assertSame(10, $report['pages']->total());
        $this->assertStringNotContainsString('secret@example.com', json_encode($report));
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.analytics.users.insights', ['range' => 'this_month', 'platform' => 'web']))
            ->assertOk()->assertSee('How well do you know musical terms?')->assertSee('Chord Finder')->assertSee('Circle of Fifths')
            ->assertDontSee('secret@example.com')->assertDontSee('notes=C,E,G');
        if ($dir = getenv('ANALYTICS_PREVIEW_DIR')) { @mkdir($dir, 0777, true); file_put_contents($dir.'/overview.html', $response->getContent()); }
    }

    public function test_only_server_browser_pageviews_count_and_dates_use_openings_rather_than_visit_start()
    {
        $member = Model::withoutEvents(function () { return create(User::class); });
        $excluded = Model::withoutEvents(function () { return create(User::class, ['email' => ' ArthurVillar@gmail.com ']); });
        $route = 'tools.chord-finder.index'; $url = 'https://learn.pianolit.com/chord-finder';
        $this->record($route, $url, ['occurred_at' => '2026-10-01 00:00:00'], ['started_at' => '2026-09-30 23:59:00']);
        $this->record($route, $url, [], ['user_id' => $member->id, 'platform' => 'webapp']);
        $this->record($route, $url, [], ['user_id' => $excluded->id]);
        $linked = $this->record($route, $url);
        DB::table('activity_visit_links')->insert(['visit_id' => DB::table('activity_events')->where('id', $linked)->value('visit_id'), 'user_id' => $excluded->id,
            'linked_at_utc' => '2026-10-20 14:01:00', 'reason' => 'login']);
        foreach ([['type' => 'request'], ['type' => 'media_play'], ['source' => 'browser'], ['source' => 'redis'],
            ['status' => 404], ['status' => 301], ['method' => 'POST'], ['visit_id' => null], ['occurred_at' => '2026-09-30 23:59:59'],
            ['occurred_at' => '2026-11-01 00:00:00']] as $event) $this->record($route, $url, $event);
        $this->record($route, $url, [], ['platform' => 'app']);
        $this->assertSame(2, (new PageViews)->get($this->filters())['total']);
        $this->assertSame(1, (new PageViews)->get($this->filters(['audience' => 'registered']))['total']);
        $this->assertSame(1, (new PageViews)->get($this->filters(['audience' => 'anonymous']))['total']);
        $this->assertSame(1, (new PageViews)->get($this->filters(['platform' => 'web']))['total']);
        $this->assertSame(1, (new PageViews)->get($this->filters(['platform' => ['webapp', 'app']]))['total']);
        $this->assertSame(0, (new PageViews)->get($this->filters(['platform' => 'app']))['total']);
    }

    public function test_event_url_falls_back_to_its_owned_page_and_unknown_data_is_not_home()
    {
        $id = $this->record(null, null);
        $event = DB::table('activity_events')->where('id', $id)->first();
        $pageId = (string) Str::uuid();
        DB::table('activity_pages')->insert(['id' => $pageId, 'visit_id' => $event->visit_id, 'visitor_id' => $event->visitor_id,
            'platform' => 'web', 'url' => 'https://learn.pianolit.com/circle-of-fifths', 'route_name' => 'tools.circle-of-fifths', 'opened_at' => $event->occurred_at]);
        DB::table('activity_events')->where('id', $id)->update(['page_id' => $pageId]);
        $this->record('quizzes.show', null);
        $this->record(null, null);
        $report = (new PageViews)->get($this->filters());
        $this->assertSame(3, $report['total']);
        $this->assertSame(1, $report['focus']['circle-of-fifths']->total);
        $this->assertSame(1, $report['hubs']['unknown']->total);
        $this->assertFalse($report['pages']->getCollection()->contains('name', 'Home'));
        $this->assertSame('Page not recorded', Presentation::page((object) []));
        $this->assertSame('Chord Finder', Presentation::page((object) ['route_name' => 'tools.chord-finder.index']));
        $this->assertSame('Circle of Fifths', Presentation::page((object) ['route_name' => 'tools.circle-of-fifths']));
        $this->assertSame('Blog', Presentation::page((object) ['route_name' => 'posts.index']));
        $this->assertSame('Profile', Presentation::page((object) ['route_name' => 'users.profile', 'url' => 'https://blog.pianolit.com/profile']));
        $secret = PageIdentity::describe((object) ['route_name' => 'password.reset', 'url' => 'https://pianolit.com/password/reset/private-token?email=secret@example.com']);
        $this->assertSame('Password reset', $secret->name);
        $this->assertNull($secret->path);
        $this->assertStringNotContainsString('private-token', json_encode($secret));
    }

    public function test_hub_filter_and_pagination_do_not_truncate_totals_and_queries_stay_bounded()
    {
        for ($i = 0; $i < 25; $i++) $this->record('quizzes.show', 'https://learn.pianolit.com/quizzes/quiz-'.$i);
        $this->record('home', 'https://pianolit.com/');
        DB::enableQueryLog(); DB::flushQueryLog();
        $report = (new PageViews)->get($this->filters(), 'learn');
        $queries = DB::getQueryLog(); DB::disableQueryLog();
        $this->assertLessThanOrEqual(2, count($queries));
        $this->assertSame(26, $report['total']);
        $this->assertSame(25, $report['focus']['quizzes']->total);
        $this->assertSame(25, $report['pages']->total());
        $this->assertCount(12, $report['pages']->items());
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.analytics.users.insights', ['range' => 'this_month', 'platform' => ['web'], 'page_hub' => 'learn', 'pages_page' => 2]))
            ->assertOk()->assertSee('Pageviews by hub')->assertSee('Pages in Learn');
        $this->assertSame('all', $response->viewData('filters')->values['audience']);
        $this->assertCount(12, $response->viewData('pages')->items());
        $this->assertStringContainsString('page_hub=learn', $response->viewData('pages')->nextPageUrl());
        $this->assertStringContainsString('pages_page=3', $response->viewData('pages')->nextPageUrl());
        $this->assertStringContainsString('platform%5B0%5D=web', $response->viewData('pages')->nextPageUrl());
        if ($dir = getenv('ANALYTICS_PREVIEW_DIR')) { @mkdir($dir, 0777, true); file_put_contents($dir.'/pageviews.html', $response->getContent()); }
    }

    public function test_last_pages_keep_their_recorded_paths_and_do_not_all_render_as_home()
    {
        foreach (['musical-terms', 'composers'] as $slug) {
            $id = $this->record('quizzes.show', 'https://learn.pianolit.com/quizzes/'.$slug);
            $event = DB::table('activity_events')->where('id', $id)->first();
            DB::table('activity_pages')->insert(['id' => (string) Str::uuid(), 'visit_id' => $event->visit_id, 'visitor_id' => $event->visitor_id,
                'platform' => 'web', 'route_name' => $event->route_name, 'url' => $event->url, 'opened_at' => $event->occurred_at]);
        }
        $report = (new Insights)->get($this->filters());
        $this->assertCount(2, $report['finalPages']);
        $this->assertSame(['Quizzes · Composers', 'Quizzes · Musical terms'], $report['finalPages']->map(function ($page) { return Presentation::page($page); })->all());
    }
}
