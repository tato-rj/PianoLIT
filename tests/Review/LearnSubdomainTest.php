<?php

namespace Tests\Review;

use App\{Admin, Pianist, User};
use App\CrashCourse\CrashCourse;
use App\Events\PurchaseMade;
use App\Infograph\Infograph;
use App\Quiz\Quiz;
use App\Providers\RouteServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\{DB, Event, Mail, Redis, Storage};
use Stevebauman\Location\Facades\Location;

class LearnSubdomainTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
        Location::shouldReceive('get')->andReturn(false);
    }

    protected function prepareUrlForRequest($uri)
    {
        return strpos($uri, 'http') === 0 ? $uri : parent::prepareUrlForRequest($uri);
    }

    private function useDomain($baseUrl)
    {
        config(['app.url' => $baseUrl, 'app.short_url' => parse_url($baseUrl, PHP_URL_HOST)]);
        app('router')->setRoutes(new RouteCollection);
        (new RouteServiceProvider($this->app))->map();
        $routes = app('router')->getRoutes();
        $routes->refreshNameLookups();
        app('url')->setRoutes($routes);
        app('url')->forceRootUrl(null);
        app('url')->forceScheme(parse_url($baseUrl, PHP_URL_SCHEME));
    }

    public static function domains()
    {
        return [['https://pianolit.com'], ['http://pianolit.test']];
    }

    /** @dataProvider domains */
    public function test_all_learning_urls_keep_route_names_on_the_new_host($baseUrl)
    {
        $this->useDomain($baseUrl);
        $host = parse_url($baseUrl, PHP_URL_HOST);
        $scheme = parse_url($baseUrl, PHP_URL_SCHEME);
        $destinations = [
            'learn.home' => ['', []],
            'tools.circle-of-fifths' => ['/circle-of-fifths', []],
            'tools.chord-finder.index' => ['/chord-finder', []],
            'tools.chord-finder.analyse' => ['/chord-finder/analyse', []],
            'tools.scales.index' => ['/scales', []],
            'tools.scales.generate' => ['/scales/generate', []],
            'tools.arpeggios.index' => ['/arpeggios', []],
            'tools.arpeggios.generate' => ['/arpeggios/generate', []],
            'crashcourses.index' => ['/crashcourses', []],
            'crashcourses.video' => ['/crashcourses/video', []],
            'crashcourses.cancel' => ['/crashcourses/cancel', []],
            'crashcourses.show' => ['/crashcourses/example', ['crashcourse' => 'example']],
            'crashcourses.signup' => ['/crashcourses/example/signup', ['crashcourse' => 'example']],
            'resources.infographs.index' => ['/infographics', []],
            'resources.infographs.show' => ['/infographics/example', ['infograph' => 'example']],
            'infographs.download' => ['/infographics/example/download', ['infograph' => 'example']],
            'infographs.update-score' => ['/infographics/example/update-score', ['infograph' => 'example']],
            'resources.pianists.index' => ['/great-pianists', []],
            'resources.pianists.show' => ['/great-pianists/example', ['pianist' => 'example']],
            'quizzes.index' => ['/quizzes', []],
            'quizzes.show' => ['/quizzes/example', ['quiz' => 'example']],
            'quizzes.feedback' => ['/quizzes/example/feedback', ['quiz' => 'example']],
        ];
        foreach (['', 'blog.', 'shop.', 'learn.', 'my.', 'admin.'] as $prefix) {
            $request = Request::create($scheme.'://'.$prefix.$host);
            $this->app->instance('request', $request);
            app('url')->setRequest($request);
            foreach ($destinations as $name => [$path, $parameters]) {
                $url = route($name, $parameters);
                $this->assertSame($scheme.'://learn.'.$host.$path, $url);
                $this->assertSame($path ?: '/', route($name, $parameters, false));
                $route = app('router')->getRoutes()->getByName($name);
                foreach ($route->methods() as $method) {
                    $matched = app('router')->getRoutes()->match(Request::create($url, $method));
                    $this->assertSame($name, $matched->getName());
                    $this->assertSame('learn.'.$host, $matched->getDomain());
                }
                $this->assertContains('web', $route->middleware());
                $this->assertContains('log.web', $route->middleware());
                $this->assertContains('location.update', $route->middleware());
            }
        }
    }

    public static function legacyUrls()
    {
        return [
            ['/tools/circle-of-fifths', '/circle-of-fifths'],
            ['/tools/circle-of-fifths/?utm_source=email&next=%2Ftools%2Fscales', '/circle-of-fifths/?utm_source=email&next=%2Ftools%2Fscales'],
            ['/tools/chord-finder', '/chord-finder'], ['/tools/chord-finder/', '/chord-finder/'],
            ['/tools/scales?key=C%2B&source=A%20%26%20B', '/scales?key=C%2B&source=A%20%26%20B'],
            ['/tools/arpeggios', '/arpeggios'], ['/tools/arpeggios/', '/arpeggios/'],
            ['/tools/scales/missing?key=C', '/scales/missing?key=C'],
            ['/crashcourses', '/crashcourses'], ['/crashcourses/', '/crashcourses/'],
            ['/crashcourses/example?utm_source=email&next=%2Fresources%2Finfographs', '/crashcourses/example?utm_source=email&next=%2Fresources%2Finfographs'],
            ['/crashcourses/video?path=example%2Flesson-1.mp4', '/crashcourses/video?path=example%2Flesson-1.mp4'],
            ['/crashcourses/cancel?email=test%2Bcourse%40example.test', '/crashcourses/cancel?email=test%2Bcourse%40example.test'],
            ['/resources/infographs?topics%5B0%5D=theory&page=2', '/infographics?topics%5B0%5D=theory&page=2'],
            ['/resources/infographs/', '/infographics/'], ['/resources/infographs/example', '/infographics/example'],
            ['/infographics/example?utm_content=A%20%26%20B', '/infographics/example?utm_content=A%20%26%20B'],
            ['/resources/great-pianists', '/great-pianists'], ['/resources/great-pianists/example', '/great-pianists/example'],
            ['/great-pianists/example', '/great-pianists/example'],
            ['/quizzes', '/quizzes'], ['/quizzes/example?level=beginner', '/quizzes/example?level=beginner'],
        ];
    }

    public function test_learn_home_is_an_organized_directory_with_public_destinations_and_a_local_newsletter()
    {
        $response = $this->get('http://learn.localhost/')->assertOk()
            ->assertSee('PianoLIT Learn')->assertDontSee('Coming up soon!');
        $dom = new \DOMDocument; @$dom->loadHTML($response->getContent()); $xpath = new \DOMXPath($dom);
        $this->assertSame(6, $xpath->query('//a[contains(@class, "resource-card") and @aria-labelledby and @aria-describedby]')->length);
        foreach (['tools.circle-of-fifths', 'tools.chord-finder.index', 'crashcourses.index', 'resources.infographs.index', 'quizzes.index', 'resources.pianists.index'] as $route) {
            $this->assertSame(1, $xpath->query('//a[contains(@class, "resource-card") and @href="'.route($route).'"]')->length);
        }
        $this->assertSame(0, $xpath->query('//nav[@aria-label="Explore Learn"]')->length);
        foreach (['tools.scales.index', 'tools.arpeggios.index'] as $route) {
            $this->assertSame(1, $xpath->query('//main//a[@href="'.route($route).'"]')->length);
        }
        $this->assertSame(1, $xpath->query('//nav[@aria-label="Breadcrumb"]//a[@href="http://localhost"]')->length);
        $this->assertSame(1, $xpath->query('//nav[@aria-label="Breadcrumb"]//*[@aria-current="page" and contains(., "Learn")]')->length);
        $this->assertSame(1, $xpath->query('//button[@data-view="newsletter-subscription" and @data-url="http://learn.localhost/subscriptions/modal"]')->length);
        $this->assertSame(1, $xpath->query('//header[contains(@class, "site-header")]//button[@data-view="newsletter-subscription" and @data-url="http://learn.localhost/subscriptions/modal"]')->length);
        foreach (['/timeline', '/birthdays', '/true-or-false', '/riddles', '/tools/staff'] as $retired) {
            $this->assertSame(0, $xpath->query('//main//a[contains(@href, "'.$retired.'")]')->length);
        }
    }

    /** @dataProvider legacyUrls */
    public function test_old_read_urls_redirect_with_raw_queries_and_clean_paths($old, $new)
    {
        $this->useDomain('http://pianolit.test');
        foreach (['pianolit.test', 'www.pianolit.test', 'my.pianolit.test'] as $host) {
            foreach (['GET', 'HEAD'] as $method) {
                $this->call($method, 'http://'.$host.$old)->assertStatus(301)->assertRedirect('http://learn.pianolit.test'.$new);
            }
        }
    }

    private function content()
    {
        return Model::withoutEvents(function () {
            return [
                create(CrashCourse::class, ['title' => 'Music course example', 'published_at' => now()]),
                create(Infograph::class, ['name' => 'Theory infographic example', 'published_at' => now()]),
                create(Pianist::class, ['name' => 'Pianist example', 'date_of_birth' => '1900-01-01', 'date_of_death' => null]),
                create(Quiz::class, ['title' => 'Quiz example', 'published_at' => now(), 'questions' => serialize([['Q' => 'Which instrument has keys?', 'A' => ['Piano[x]', 'Violin']]])]),
            ];
        });
    }

    public function test_learning_pages_render_canonical_links_forms_and_existing_video_sources()
    {
        [$course, $infograph, $pianist, $quiz] = $this->content();
        foreach (['crashcourses.index' => $course->title, 'resources.infographs.index' => $infograph->showRoute(), 'resources.pianists.index' => $pianist->name, 'quizzes.index' => $quiz->title] as $route => $title) {
            $this->get(route($route))->assertOk()->assertSee($title);
        }
        $this->get(route('crashcourses.show', $course))->assertOk()->assertSee($course->title)
            ->assertSee('action="http://learn.localhost/crashcourses/'.$course->slug.'/signup"', false)
            ->assertSee('href="http://localhost"', false);
        $this->get(route('resources.infographs.show', $infograph))->assertOk()->assertSee($infograph->name)
            ->assertSee('data-url="http://learn.localhost/infographics/'.$infograph->slug.'/update-score"', false);
        $this->get(route('resources.pianists.show', $pianist))->assertOk()->assertSee($pianist->name)
            ->assertSee('<link rel="canonical" href="http://learn.localhost/great-pianists/'.$pianist->slug.'"', false);
        $this->get(route('quizzes.show', $quiz))->assertOk()->assertSee('Which instrument has keys?')
            ->assertSee('http://learn.localhost/quizzes/'.$quiz->slug.'/feedback', false);
        config(['services.googlecloud.crashcourses' => 'https://media.example.test/courses/']);
        $this->get(route('crashcourses.video', ['path' => 'music/lesson-1.mp4']))->assertOk()
            ->assertSee('https://media.example.test/courses/music/lesson-1.mp4', false);
        Mail::assertNothingSent(); Mail::assertNothingQueued();
    }

    /** @dataProvider domains */
    public function test_learning_navigation_is_absolute_without_a_configured_scheme($baseUrl)
    {
        $this->useDomain($baseUrl);
        config(['app.url' => parse_url($baseUrl, PHP_URL_HOST)]);
        $learn = parse_url($baseUrl, PHP_URL_SCHEME).'://learn.'.parse_url($baseUrl, PHP_URL_HOST);
        $this->get(route('resources.pianists.index'))->assertOk()
            ->assertSee('href="'.$baseUrl.'"', false)
            ->assertSee('href="'.$learn.'/login"', false)
            ->assertSee('data-url="'.$learn.'/subscriptions/modal"', false);
        $this->assertSame($learn.'/circle-of-fifths', route('tools.circle-of-fifths'));
        $this->assertSame($learn.'/infographics', route('resources.infographs.index'));
        $this->assertSame($learn.'/great-pianists', route('resources.pianists.index'));
        $this->assertSame($baseUrl, route('home'));
        $this->assertSame($learn.'/shop/1/thank-you', route('shop.success', 1));
    }

    public function test_current_and_legacy_signup_and_voting_endpoints_retain_csrf()
    {
        [$course, $infograph] = $this->content();
        $this->app['env'] = 'review-csrf';
        $this->withExceptionHandling()->withSession(['_token' => 'learn-csrf']);
        foreach (['http://learn.localhost/crashcourses/'.$course->slug.'/signup', 'http://localhost/crashcourses/'.$course->slug.'/signup'] as $url) {
            $this->postJson($url)->assertStatus(419);
            $this->withHeader('X-CSRF-TOKEN', 'learn-csrf')->postJson($url, ['started_at' => now()->subSeconds(10)->toDateTimeString(), 'first_name' => 'Example', 'email' => 'invalid'])
                ->assertStatus(422)->assertJsonValidationErrors('email');
            $this->flushHeaders();
        }
        foreach (['http://learn.localhost/infographics/'.$infograph->slug.'/update-score', 'http://localhost/infographs/'.$infograph->slug.'/update-score'] as $url) {
            $this->postJson($url, ['liked' => 1])->assertStatus(419);
        }
        $this->assertDatabaseCount('crash_course_subscriptions', 0);
        Mail::assertNothingSent(); Mail::assertNothingQueued();
    }

    public function test_quiz_feedback_keeps_scoring_on_new_and_already_open_legacy_pages()
    {
        [, , , $quiz] = $this->content();
        foreach (['http://learn.localhost', 'http://localhost'] as $origin) {
            $this->get($origin.'/quizzes/'.$quiz->slug.'/feedback?answers%5B0%5D=0')->assertOk()->assertSee('1 out of 1');
        }
        $this->assertSame(2, DB::table('quiz_results')->where('quiz_id', $quiz->id)->where('score', 1)->count());
    }

    public function test_infographic_downloads_keep_session_access_and_the_original_file()
    {
        [, $infograph] = $this->content();
        Storage::fake('public');
        Storage::disk('public')->put($infograph->cover_path, 'Original infographic bytes');
        Event::fake([PurchaseMade::class]);
        $this->withExceptionHandling()->get(route('infographs.download', $infograph))->assertRedirect('http://learn.localhost/login');
        $user = Model::withoutEvents(function () { return create(User::class); });
        $this->actingAs($user, 'web');
        $this->get(route('infographs.download', ['infograph' => $infograph, 'type' => 'download']))->assertOk()->assertDownload(basename($infograph->cover_path));
        $this->assertDatabaseHas('purchases', ['user_id' => $user->id, 'item_type' => Infograph::class, 'item_id' => $infograph->id]);
        $this->get(route('infographs.download', $infograph))->assertRedirect('http://learn.localhost/shop/'.$user->purchases()->first()->id.'/thank-you?type=free');
        $this->assertDatabaseCount('purchases', 1);
        Mail::assertNothingSent(); Mail::assertNothingQueued();
    }

    public function test_old_paths_on_learn_redirect_once_and_unknown_paths_do_not_loop()
    {
        $this->get('http://learn.localhost/resources/infographs?utm_source=email')->assertStatus(301)->assertRedirect('http://learn.localhost/infographics?utm_source=email');
        $this->get('http://learn.localhost/resources/great-pianists')->assertStatus(301)->assertRedirect('http://learn.localhost/great-pianists');
        foreach (['circle-of-fifths', 'chord-finder', 'scales', 'arpeggios'] as $path) {
            $this->get('http://learn.localhost/tools/'.$path.'?utm_source=email')->assertStatus(301)->assertRedirect('http://learn.localhost/'.$path.'?utm_source=email');
            $this->get('http://learn.localhost/'.$path)->assertOk();
        }
        foreach (['crashcourses', 'infographics', 'great-pianists', 'quizzes', 'circle-of-fifths', 'chord-finder', 'scales', 'arpeggios'] as $path) {
            $this->withExceptionHandling()->get('http://learn.localhost/'.$path.'/missing/unknown')->assertNotFound();
        }
    }

    public function test_tagged_learning_arrival_is_anonymous_and_canonical_after_the_redirect()
    {
        $this->get('http://localhost/resources/great-pianists?utm_source=email')->assertStatus(301)->assertRedirect('http://learn.localhost/great-pianists?utm_source=email');
        $this->assertDatabaseCount('campaign_visits', 0);
        $this->get('http://learn.localhost/great-pianists?utm_source=email')->assertOk()->assertDontSee('Campaign link measurement');
        $this->get('http://learn.localhost/great-pianists?utm_source=email')->assertOk();
        $this->assertDatabaseCount('campaign_visits', 1);
        $this->assertDatabaseHas('campaign_visits', ['landing_url' => 'http://learn.localhost/great-pianists', 'user_id' => null, 'consented_at' => null]);
    }

    public function test_tool_pages_render_clean_canonical_navigation_and_calculation_urls()
    {
        $tools = [
            'circle-of-fifths' => 'Circle of Fifths', 'chord-finder' => 'Chord Finder',
            'scales' => 'Scales Tutor', 'arpeggios' => 'Arpeggios Tutor',
        ];
        foreach ($tools as $path => $title) {
            $response = $this->get('http://learn.localhost/'.$path)->assertOk()->assertSee($title)
                ->assertSee('<link rel="canonical" href="http://learn.localhost/'.$path.'"', false)
                ->assertSee('href="http://localhost"', false)
                ->assertSee('href="http://localhost/support"', false)
                ->assertSee('data-url="http://learn.localhost/subscriptions/modal"', false)
                ->assertDontSee('http://learn.localhost/tools/', false);
            $response->assertSee('href="http://learn.localhost/login"', false);
            foreach (['localhost', 'blog.localhost', 'shop.localhost'] as $host) {
                $this->get('http://'.$host.'/tools/'.$path.'?utm_source=menu')->assertStatus(301)
                    ->assertRedirect('http://learn.localhost/'.$path.'?utm_source=menu');
            }
        }
        $this->get(route('tools.chord-finder.index'))->assertSee('data-url="http://learn.localhost/chord-finder/analyse"', false);
        foreach (['scales', 'arpeggios'] as $tool) {
            $this->get(route('tools.'.$tool.'.index'))->assertSee('data-url="http://learn.localhost/'.$tool.'/generate"', false);
        }
    }

    public function test_tool_calculations_keep_results_on_learn_and_already_open_legacy_pages()
    {
        foreach (['scales' => ['modes', ['c', 'd', 'e', 'f', 'g', 'a', 'b', 'c']], 'arpeggios' => ['positions', ['c', 'e', 'g', 'c']]] as $tool => [$group, $notes]) {
            $canonical = $this->getJson(route('tools.'.$tool.'.generate', ['key' => 'C', 'dev' => 1]))->assertOk();
            $canonical->assertJsonPath($group.'.0.notes', $notes);
            foreach (['http://learn.localhost', 'http://localhost'] as $origin) {
                $this->getJson($origin.'/tools/'.$tool.'/generate?key=C&dev=1')->assertOk()->assertExactJson($canonical->json());
            }
            $this->get(route('tools.'.$tool.'.generate', ['key' => 'Am']))->assertOk()->assertSee('data-notes=', false);
        }
        $parameters = ['notes' => ['C', 'E', 'G'], 'tool' => 'button'];
        foreach (['http://learn.localhost/chord-finder/analyse', 'http://localhost/tools/chord-finder/analyse'] as $url) {
            $query = http_build_query($parameters);
            $this->get($url.'?'.$query)->assertOk()->assertSee('chords-results')->assertSee('data-notes=', false);
            $this->getJson($url.'?'.$query.'&dev=1')->assertOk()->assertJsonPath('chords.0.notes', ['c', 'e', 'g']);
        }
        $this->withExceptionHandling();
        $this->getJson(route('tools.chord-finder.analyse', ['notes' => ['C']]))->assertStatus(422);
        $this->getJson(route('tools.scales.generate', ['key' => 'invalid']))->assertStatus(422);
        $this->getJson(route('tools.arpeggios.generate', ['key' => 'invalid']))->assertStatus(422);
        Mail::assertNothingSent(); Mail::assertNothingQueued();
    }

    public function test_tool_campaign_arrival_uses_the_clean_learning_destination()
    {
        $this->get('http://localhost/tools/circle-of-fifths?utm_source=email')->assertStatus(301)
            ->assertRedirect('http://learn.localhost/circle-of-fifths?utm_source=email');
        $this->assertDatabaseCount('campaign_visits', 0);
        $this->get('http://learn.localhost/circle-of-fifths?utm_source=email')->assertOk();
        $this->get('http://learn.localhost/circle-of-fifths?utm_source=email')->assertOk();
        $this->assertDatabaseCount('campaign_visits', 1);
        $this->assertDatabaseHas('campaign_visits', ['landing_url' => 'http://learn.localhost/circle-of-fifths', 'user_id' => null, 'consented_at' => null]);
    }
}
