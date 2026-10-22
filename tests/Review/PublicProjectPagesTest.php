<?php

namespace Tests\Review;

use App\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\{DB, Redis};
use Stevebauman\Location\Facades\Location;

class PublicProjectPagesTest extends ReviewTestCase
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
        config(['app.url' => parse_url($baseUrl, PHP_URL_HOST), 'app.short_url' => parse_url($baseUrl, PHP_URL_HOST)]);
        app('router')->setRoutes(new RouteCollection);
        (new RouteServiceProvider($this->app))->map();
        $routes = app('router')->getRoutes();
        $routes->refreshNameLookups();
        app('url')->setRoutes($routes);
        app('url')->forceRootUrl(null);
        app('url')->forceScheme(parse_url($baseUrl, PHP_URL_SCHEME));
    }

    private function xpath($html)
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        return new \DOMXPath($dom);
    }

    public function test_homepage_piano_is_decorative_even_with_an_active_free_pick()
    {
        $this->useDomain('http://pianolit.test');
        $piece = Model::withoutEvents(function () {
            return create(\App\Piece::class, ['name' => 'Current Free Pick', 'audio_path' => 'app/audio/current.mp3', 'score_path' => 'app/score/current.pdf']);
        });
        \App\FreePick::create(['piece_id' => $piece->id, 'scheduled_at' => now('UTC')->subHour()]);
        $response = $this->get('http://pianolit.test')->assertOk()
            ->assertSee('images/home/editorial/piano.webp', false)
            ->assertDontSee('data-home-piano', false)->assertDontSee('data-score-toggle', false)
            ->assertDontSee('home-piano.js', false)->assertDontSee('pdfjs/pdf.js', false)
            ->assertDontSee('app/audio/current.mp3', false)->assertDontSee('app/score/current.pdf', false);
        $xpath = $this->xpath($response->getContent());
        $this->assertSame(0, $xpath->query('//audio | //canvas')->length);
        $this->assertSame(1, $xpath->query('//div[@class="project-home__instrument" and @aria-hidden="true"]/img[@alt=""]')->length);
    }

    public function test_metronome_is_standalone_on_its_own_host_and_linked_from_the_homepage()
    {
        foreach (['http://pianolit.test', 'https://pianolit.com'] as $website) {
            $this->useDomain($website);
            $origin = str_replace('://', '://metronome.', $website);
            foreach (['GET', 'HEAD'] as $method) {
                $route = app('router')->getRoutes()->match(Request::create($origin, $method));
                $this->assertSame('metronome.home', $route->getName());
                $this->assertContains('log.web', $route->middleware());
            }
            $response = $this->get($origin)->assertOk()
                ->assertSee('Metronome | PianoLIT')->assertSee('href="'.$origin.'"', false)
                ->assertSee('css/metronome.css?id=', false)->assertSee('js/metronome.js?id=', false)
                ->assertDontSee('Arthur Villar')->assertDontSee('data-project-deck', false);
            $xpath = $this->xpath($response->getContent());
            $this->assertSame(0, $xpath->query('//footer | //header[contains(@class, "site-header")]')->length);
            $this->assertSame(1, $xpath->query('//body/a[@class="project-watermark" and @href="'.$website.'" and @aria-label="PianoLIT home"]/img[@alt="" and @src="'.asset(mix('images/brand/icon-white.svg')).'"]')->length);
            $this->assertSame(1, $xpath->query('//button[@data-metronome and @aria-pressed="false"]')->length);
            $this->assertSame(3, $xpath->query('//*[@class="segmented-control"]')->length);
            $this->assertSame(1, $xpath->query('//select[@id="metronome-sound" and contains(@class, "form-control")]')->length);
            $this->assertSame(4, $xpath->query('//select[@id="metronome-sound"]/option')->length);
            $this->assertSame(1, $xpath->query('//input[@name="metronome-path" and @role="switch"]')->length);
            $this->assertSame(1, $xpath->query('//input[@data-settings-color and @value="#0055fe"]')->length);
            $this->assertSame(1, $xpath->query('//button[@data-settings-close and contains(@class, "btn-primary")]')->length);
            $this->assertSame($origin, route('metronome.home'));
            $this->assertSame($website, route('home'));
            $this->call('HEAD', $origin)->assertOk();
            $this->withExceptionHandling()->get($origin.'/does-not-exist')->assertNotFound();
            $this->disableExceptionHandling();

            $home = $this->get($website)->assertOk();
            $cards = $this->xpath($home->getContent())->query('//a[@data-project]');
            $this->assertSame(7, $cards->length);
            $this->assertSame('webapp', $cards->item(0)->getAttribute('data-project'));
            $this->assertSame('ios', $cards->item(1)->getAttribute('data-project'));
            $this->assertSame($origin, $cards->item(6)->getAttribute('href'));
            $home->assertSee('images/home/editorial/metronome.webp?id=', false);
            $webapp = app('router')->getRoutes()->match(Request::create(str_replace('://', '://my.', $website), 'GET'));
            $this->assertSame('webapp.discover', $webapp->getName());
        }
    }

    public static function hubs()
    {
        $cases = [];
        foreach (['http://pianolit.test', 'https://pianolit.com'] as $website) {
            foreach (['shop' => 'shop.home', 'games' => 'games.home', 'learn' => 'learn.home'] as $hub => $route) {
                $cases[] = [$website, $hub, $route];
            }
        }
        return $cases;
    }

    /** @dataProvider hubs */
    public function test_hub_roots_share_the_header_and_matching_artwork_with_absolute_navigation($website, $hub, $name)
    {
        $this->useDomain($website);
        $origin = str_replace('://', '://'.$hub.'.', $website);
        foreach (['GET', 'HEAD'] as $method) {
            $route = app('router')->getRoutes()->match(Request::create($origin, $method));
            $this->assertSame($name, $route->getName());
            $this->assertContains('web', $route->middleware());
            $this->assertContains('log.web', $route->middleware());
        }
        $response = $this->get($origin)->assertOk()
            ->assertDontSee('images/shop/coming-soon.webp', false)
            ->assertSee('<link rel="canonical" href="'.$origin.'"', false);
        if ($hub === 'learn') {
            $response->assertSee('PianoLIT Learn')->assertSee('Interactive tools')->assertSee('Courses &amp; guides', false)->assertDontSee('Coming up soon!');
        } else {
            $response->assertSee('Coming up soon!')->assertSee('images/home/projects/'.$hub.'.svg?id=', false);
        }
        $xpath = $this->xpath($response->getContent());
        $this->assertSame(1, $xpath->query('//header[contains(@class, "site-header")]')->length);
        $this->assertSame($website, $xpath->query('//header//a[@aria-label="PianoLIT home"]')->item(0)->getAttribute('href'));
        $this->assertSame(0, $xpath->query('//header//a[@href="https://www.youtube.com/pianolit"]')->length);
        $this->assertSame(1, $xpath->query('//header//a[@href="'.$origin.'/login"]')->length);
        $this->assertSame(1, $xpath->query('//header//a[@aria-label="Sign in" and contains(@class, "btn-raw")]')->length);
        $response->assertSee('data-lucide-name="user-round"', false);
        $this->assertSame(1, $xpath->query('//main//a[@href="'.$website.'"]')->length);
        $this->assertSame(1, $xpath->query('//footer[contains(@class, "site-footer")]')->length);
        foreach (['contact-us', 'privacy-policy', 'terms-of-service'] as $path) {
            $this->assertSame(1, $xpath->query('//footer//a[@href="'.$website.'/'.$path.'"]')->length);
        }
        $this->assertSame($origin, route($name));
        $this->assertSame($origin.'/attribution/consent', route('attribution.consent'));
        $this->call('HEAD', $origin)->assertOk();
    }

    public function test_public_hub_menu_links_across_hosts_and_stays_off_the_homepage_and_webapp()
    {
        foreach (['http://pianolit.test', 'https://pianolit.com'] as $website) {
            $this->useDomain($website);
            $scheme = parse_url($website, PHP_URL_SCHEME).'://';
            $host = parse_url($website, PHP_URL_HOST);
            $destinations = [
                'PianoLIT home' => $website,
                'Web app' => $scheme.'my.'.$host,
                'iOS app' => config('app.stores.ios'),
                'Shop' => $scheme.'shop.'.$host,
                'Games' => $scheme.'games.'.$host,
                'Learn' => $scheme.'learn.'.$host,
                'Blog' => $scheme.'blog.'.$host,
                'Metronome' => $scheme.'metronome.'.$host,
            ];
            foreach (['shop.', 'games.', 'learn.', 'blog.', ''] as $prefix) {
                $origin = $scheme.$prefix.$host;
                $response = $this->get($origin.($prefix === '' ? '/contact-us' : ''))->assertOk();
                $xpath = $this->xpath($response->getContent());
                $this->assertSame(1, $xpath->query('//header//ul/li[last()]/button[@data-bs-toggle="offcanvas" and @data-bs-target="#public-hub-menu" and @aria-controls="public-hub-menu"]')->length);
                $this->assertSame(1, $xpath->query('//header//a[@aria-label="Sign in" and @href="'.$origin.'/login"]')->length);
                $this->assertSame(1, $xpath->query('//div[@id="public-hub-menu" and contains(@class, "offcanvas-end") and @tabindex="-1" and @aria-labelledby="public-hub-menu-title"]')->length);
                $this->assertSame(1, $xpath->query('//*[@id="public-hub-menu"]//button[@data-bs-dismiss="offcanvas" and @aria-label="Close hub menu"]')->length);
                $this->assertSame(8, $xpath->query('//nav[@aria-label="PianoLIT hubs"]//a')->length);
                foreach ($destinations as $label => $destination) {
                    $this->assertSame(1, $xpath->query('//nav[@aria-label="PianoLIT hubs"]//a[@href="'.$destination.'"]/span[text()="'.$label.'"]')->length, $origin.' → '.$label);
                }
                $this->assertSame(1, $xpath->query('//nav[@aria-label="PianoLIT hubs"]//a[@aria-current="true" and @href="'.$origin.'"]')->length);
            }
            foreach ([$website, $scheme.'my.'.$host.'/login', $scheme.'my.'.$host] as $url) {
                $this->get($url)->assertOk()->assertDontSee('id="public-hub-menu"', false)->assertDontSee('aria-label="Open hub menu"', false);
            }
        }
    }

    public function test_named_account_menu_replaces_sign_in_and_keeps_logout_on_each_host()
    {
        $user = Model::withoutEvents(function () {
            return create(User::class, ['first_name' => 'Header <Example>', 'email' => 'header@example.test']);
        });
        $this->actingAs($user, 'web');
        foreach (['', 'shop.', 'games.', 'learn.', 'blog.'] as $prefix) {
            $origin = 'http://'.$prefix.'localhost';
            $response = $this->get($origin)->assertOk()->assertSee('Header &lt;Example&gt;', false);
            $xpath = $this->xpath($response->getContent());
            $this->assertSame(0, $xpath->query('//header//a[@href="'.$origin.'/login"]')->length);
            $this->assertSame($prefix === '' ? 1 : 0, $xpath->query('//header//a[@href="https://www.youtube.com/pianolit"]')->length);
            $this->assertSame(1, $xpath->query('//header//button[@data-bs-toggle="dropdown" and contains(@class, "account-circle")]')->length);
            $this->assertSame('H', trim($xpath->query('//header//button//span[contains(@class, "account-circle__initial")]')->item(0)->textContent));
            $this->assertSame(1, $xpath->query('//header//a[@href="'.$origin.'/my-profile"]')->length);
            $this->assertSame(1, $xpath->query('//header//form[@action="'.$origin.'/logout" and @method="POST"]//input[@name="_token"]')->length);
            $this->assertSame($prefix === '' ? 0 : 1, $xpath->query('//header//button[@data-bs-target="#public-hub-menu"]')->length);
        }
    }

    public function test_all_public_hosts_use_standalone_account_views_with_local_forms()
    {
        foreach (['localhost', 'my.localhost', 'shop.localhost', 'games.localhost', 'learn.localhost', 'blog.localhost'] as $host) {
            $origin = 'http://'.$host;
            foreach (['login', 'register', 'password/reset', 'password/reset/review-token'] as $page) {
                $action = $page === 'password/reset' ? 'password/email' : ($page === 'password/reset/review-token' ? 'password/reset' : $page);
                $response = $this->get($origin.'/'.$page)->assertOk()
                    ->assertSee('id="action"', false)
                    ->assertSee('action="'.$origin.'/'.$action.'"', false)
                    ->assertSee('name="_token"', false)
                    ->assertSee('name="email"', false);
                if (in_array($page, ['login', 'register'])) {
                    $response->assertSee('id="onboarding"', false);
                } else {
                    $response->assertDontSee('id="onboarding"', false)
                        ->assertDontSee("$('#onboarding').carousel", false)
                        ->assertSee('min-vh-100 align-items-center justify-content-center', false);
                }
                if ($page !== 'password/reset') $response->assertSee('name="password"', false);
                $xpath = $this->xpath($response->getContent());
                $this->assertSame(0, $xpath->query('//header//a[@href="https://www.youtube.com/pianolit"]')->length);
                $this->assertSame(0, $xpath->query('//header[contains(@class, "site-header")]')->length);
                $this->assertSame(0, $xpath->query('//footer[contains(@class, "site-footer")]')->length);
                if ($page === 'register') {
                    $this->assertSame($host === 'my.localhost' ? 'webapp' : 'web', $xpath->query('//input[@name="origin"]')->item(0)->getAttribute('value'));
                    $response->assertSee('name="password_confirmation"', false);
                } elseif ($page === 'login') {
                    $response->assertSee('Forgot password?');
                } elseif ($page === 'password/reset/review-token') {
                    $response->assertSee('name="token" value="review-token"', false)
                        ->assertSee('name="password_confirmation"', false);
                }
            }
        }
    }

    public function test_account_initial_handles_unicode_and_an_empty_name()
    {
        $html = view('components.account-circle', ['account' => (object) ['first_name' => ' élena ']])->render();
        $this->assertStringContainsString('>É</span>', $html);
        $this->assertStringContainsString('aria-label="Account menu for élena"', $html);
        $html = view('components.account-circle', ['account' => (object) ['first_name' => '']])->render();
        $this->assertStringContainsString('>U</span>', $html);
        $this->assertStringContainsString('aria-label="Account menu"', $html);
    }

    public function test_regular_public_pages_share_the_header_while_webapp_and_embedded_layouts_keep_theirs()
    {
        foreach (['/', '/contact-us', '/privacy-policy', '/terms-of-service'] as $path) {
            $response = $this->get('http://localhost'.$path)->assertOk();
            $xpath = $this->xpath($response->getContent());
            $this->assertSame(1, $xpath->query('//header[contains(@class, "site-header")]')->length, $path);
            $this->assertSame(1, $xpath->query('//footer[contains(@class, "site-footer")]')->length, $path);
            $this->assertSame($path === '/' ? 1 : 0, $xpath->query('//header//a[@href="https://www.youtube.com/pianolit"]')->length, $path);
            $this->assertSame(0, $xpath->query('//header//button[@data-bs-target="#navbar-items"]')->length);
        }
        $this->get('http://my.localhost/login')->assertOk()->assertDontSee('class="site-header', false)
            ->assertDontSee('class="site-footer', false);
        foreach (['localhost' => true, 'my.localhost' => false] as $host => $hasHeader) {
            $request = Request::create('http://'.$host.'/auth/google/example');
            $this->app->instance('request', $request);
            app('url')->setRequest($request);
            $html = view('auth.google.error', ['message' => 'Example verification message'])->render();
            $this->assertSame($hasHeader, strpos($html, 'class="site-header') !== false);
            $this->assertSame($hasHeader, strpos($html, 'class="site-footer') !== false);
        }
    }

    public function test_hub_campaign_arrivals_stay_anonymous_and_host_local()
    {
        foreach (['shop', 'games', 'learn', 'blog', 'metronome'] as $hub) {
            $origin = 'http://'.$hub.'.localhost/';
            for ($visit = 0; $visit < 2; $visit++) {
                $this->get($origin.'?utm_source=newsletter&utm_campaign=projects')->assertOk()
                    ->assertDontSee('Campaign link measurement');
            }
            $this->assertDatabaseHas('campaign_visits', ['landing_url' => $origin, 'user_id' => null, 'consented_at' => null]);
        }
        $this->assertDatabaseCount('campaign_visits', 5);
        $this->assertSame(0, DB::table('campaign_visits')->whereNotNull('user_id')->count());
    }

    public function test_excluded_account_remains_excluded_on_games()
    {
        $user = Model::withoutEvents(function () { return create(User::class, ['email' => 'arthurvillar@gmail.com']); });
        $this->actingAs($user, 'web');
        $this->get('http://games.localhost/?utm_source=newsletter')->assertOk();
        $this->get('http://metronome.localhost/?utm_source=newsletter')->assertOk();
        $this->assertDatabaseCount('campaign_visits', 0);
        $this->assertDatabaseCount('activity_events', 0);
    }
}
