<?php

namespace Tests\Review;

use App\{Admin, User};
use App\Blog\Post;
use App\Providers\RouteServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\{DB, Redis};

class BlogSubdomainTest extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Redis::shouldReceive('get')->andReturn(null);
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
        app('url')->forceRootUrl($baseUrl);
        app('url')->forceScheme(parse_url($baseUrl, PHP_URL_SCHEME));
    }

    public static function domains()
    {
        return [['https://pianolit.com'], ['http://pianolit.test']];
    }

    /** @dataProvider domains */
    public function test_blog_routes_use_the_subdomain_without_the_blog_prefix($baseUrl)
    {
        $this->useDomain($baseUrl);
        $origin = parse_url($baseUrl, PHP_URL_SCHEME).'://blog.'.parse_url($baseUrl, PHP_URL_HOST);
        foreach (['index' => '', 'show' => '/example-article', 'gift' => '/example-article/gift', 'app' => '/example-article/app'] as $name => $path) {
            $url = route('posts.'.$name, $name === 'index' ? [] : ['post' => 'example-article']);
            $this->assertSame($origin.$path, $url);
            foreach (['GET', 'HEAD'] as $method) {
                $route = app('router')->getRoutes()->match(Request::create($url, $method));
                $this->assertSame('posts.'.$name, $route->getName());
                $this->assertSame('blog.'.parse_url($baseUrl, PHP_URL_HOST), $route->getDomain());
                $this->assertContains('web', $route->middleware());
                $this->assertContains('log.web', $route->middleware());
            }
        }
        $this->assertSame('home', app('router')->getRoutes()->match(Request::create($baseUrl))->getName());
        $this->assertSame('admin.posts.index', app('router')->getRoutes()->match(Request::create(str_replace('://', '://admin.', $baseUrl).'/blog'))->getName());
    }

    public static function legacyUrls()
    {
        return [
            ['/blog', ''], ['/blog/', '/'], ['/blog?topics=technique&page=2', '?topics=technique&page=2'],
            ['/blog/example-article', '/example-article'],
            ['/blog/example-article/gift', '/example-article/gift'],
            ['/blog/example-article/app?theme=dark', '/example-article/app?theme=dark'],
            ['/blog/missing-article?utm_source=email&utm_content=A%20%26%20B&next=%2Fblog%2Fother', '/missing-article?utm_source=email&utm_content=A%20%26%20B&next=%2Fblog%2Fother'],
        ];
    }

    /** @dataProvider legacyUrls */
    public function test_old_public_and_webapp_urls_redirect_without_losing_paths_or_queries($path, $suffix)
    {
        $this->useDomain('http://pianolit.test');
        foreach (['pianolit.test', 'www.pianolit.test', 'my.pianolit.test'] as $host) {
            foreach (['GET', 'HEAD'] as $method) {
                $this->call($method, 'http://'.$host.$path)->assertStatus(301)->assertRedirect('http://blog.pianolit.test'.$suffix);
            }
        }
    }

    private function article(array $attributes = [])
    {
        return Model::withoutEvents(function () use ($attributes) {
            return create(Post::class, $attributes + ['title' => 'Subdomain article', 'published_at' => now(), 'content' => '<p>Existing article content.</p>', 'gift_path' => '/images/gifts/example.jpg']);
        });
    }

    public function test_blog_pages_keep_navigation_ajax_gifts_embedded_output_and_canonical_urls()
    {
        $post = $this->article();
        $origin = 'http://blog.localhost';
        $this->get(route('posts.index'))->assertOk()->assertSee($post->title)->assertSee(route('posts.show', $post), false);
        $response = $this->get(route('posts.show', $post))->assertOk()->assertSee('Existing article content.');
        $response->assertSee('<link rel="canonical" href="'.$origin.'/'.$post->slug.'"', false)
            ->assertSee('href="http://learn.localhost/circle-of-fifths"', false)
            ->assertSee('href="http://localhost/contact-us"', false)
            ->assertSee('data-url="'.$origin.'/subscriptions/modal"', false)
            ->assertSee('data-url="'.$origin.'/'.$post->slug.'/gift"', false);
        $this->assertSame('http://localhost', route('home'));
        $this->assertSame('http://localhost/gift', route('gift'));
        $this->assertSame($origin.'/subscriptions', route('subscriptions.store'));
        $this->get($origin.'/subscriptions/modal?view=subscription')->assertOk()
            ->assertSee('action="'.$origin.'/subscriptions"', false);
        $this->get(route('posts.gift', $post))->assertOk()->assertSee('blog-gift-modal')->assertSee($post->gift_path, false);
        $this->get(route('posts.app', $post))->assertOk()->assertSee('Existing article content.')->assertDontSee('navbar-brand');
        $json = $post->fresh()->toArray();
        $this->assertSame($origin.'/'.$post->slug.'/app', $json['app_url']);
        $this->assertArrayHasKey('cover_image', $json);
        $this->assertArrayHasKey('topics', $json);
        $this->assertSame($origin.'/'.$post->slug, route('posts.show', $post));
        $row = view('webapp.explore.rows.original', ['row' => ['collection' => $post, 'label' => 'From the blog']])->render();
        $this->assertStringContainsString('href="'.$origin.'/'.$post->slug.'"', $row);
    }

    /** @dataProvider domains */
    public function test_main_website_navigation_is_absolute_without_a_configured_scheme($baseUrl)
    {
        $this->useDomain($baseUrl);
        $host = parse_url($baseUrl, PHP_URL_HOST);
        $blog = parse_url($baseUrl, PHP_URL_SCHEME).'://blog.'.$host;
        config(['app.url' => $host]);
        app('url')->forceRootUrl(null);

        $response = $this->get(route('posts.index'))->assertOk();
        $dom = new \DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        foreach ($xpath->query('//header//a[@href] | //footer//a[@href]') as $link) {
            $href = $link->getAttribute('href');
            if (strpos($href, $host) === 0) {
                $this->fail('Website navigation must include a scheme: '.$href);
            }
        }
        $response->assertSee('href="'.parse_url($baseUrl, PHP_URL_SCHEME).'://learn.'.$host.'/circle-of-fifths"', false)
            ->assertSee('href="'.$baseUrl.'/contact-us"', false)
            ->assertSee('href="'.$blog.'"', false);
        $this->assertSame($baseUrl, $xpath->query('//a[contains(@class, "navbar-brand")]')->item(0)->getAttribute('href'));
        $this->assertSame(1, $xpath->query('//footer//a[@href="'.$baseUrl.'" and text()="Home"]')->length);
        $this->assertSame($baseUrl, route('home'));
        $this->assertSame(parse_url($baseUrl, PHP_URL_SCHEME).'://learn.'.$host.'/circle-of-fifths', route('tools.circle-of-fifths'));
        $this->assertSame('/circle-of-fifths', route('tools.circle-of-fifths', [], false));
        $this->assertSame($blog.'/subscriptions/modal', route('subscriptions.modal'));
        $this->assertSame($blog.'/subscriptions', route('subscriptions.store'));
    }

    public function test_drafts_still_require_admin_preview_on_the_new_host()
    {
        $post = $this->article(['published_at' => null]);
        $this->withExceptionHandling()->get(route('posts.show', $post))->assertNotFound();
        $this->get(route('posts.index'))->assertOk()->assertDontSee($post->title);
        $this->actingAs(create(Admin::class), 'admin');
        auth()->shouldUse('web');
        $this->get(route('posts.show', $post))->assertOk()->assertSee($post->title)->assertSee('not published');
        $this->assertSame('http://admin.localhost/blog/'.$post->id, route('admin.posts.edit', $post->id));
    }

    public function test_blog_login_and_registration_are_not_captured_as_article_slugs()
    {
        foreach (['login', 'register'] as $path) {
            $this->assertSame($path, app('router')->getRoutes()->match(Request::create('http://blog.localhost/'.$path))->getName());
            $this->get('http://blog.localhost/'.$path)->assertOk()->assertSee('action="http://blog.localhost/'.$path.'"', false);
        }
        $this->assertSame('users.profile', app('router')->getRoutes()->match(Request::create('http://blog.localhost/my-profile'))->getName());
        $this->withExceptionHandling()->get('http://blog.localhost/my-profile')->assertRedirect('http://blog.localhost/login');
    }

    public function test_blog_subscription_forms_keep_session_csrf_enforcement()
    {
        $this->app['env'] = 'review-csrf';
        $this->withExceptionHandling()->withSession(['_token' => 'blog-csrf']);
        $this->postJson('http://blog.localhost/subscriptions', ['email' => 'invalid'])->assertStatus(419);
        $this->withHeader('X-CSRF-TOKEN', 'blog-csrf')
            ->postJson('http://blog.localhost/subscriptions', ['email' => 'invalid', 'started_at' => now()->subSeconds(10)->toDateTimeString()])
            ->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_tagged_legacy_arrivals_are_measured_once_on_the_blog_host_and_stay_anonymous()
    {
        $post = $this->article();
        $tagged = route('posts.show', ['post' => $post, 'utm_source' => 'newsletter', 'utm_content' => 'story']);
        $this->get('http://localhost/blog/'.$post->slug.'?utm_source=newsletter&utm_content=story')->assertStatus(301)->assertRedirect($tagged);
        $this->assertDatabaseCount('campaign_visits', 0);
        $this->get($tagged)->assertOk()->assertDontSee('Campaign link measurement');
        $this->get($tagged)->assertOk();
        $this->assertDatabaseCount('campaign_visits', 1);
        $this->assertDatabaseHas('campaign_visits', ['landing_url' => route('posts.show', $post), 'user_id' => null, 'consented_at' => null, 'utm_source' => 'newsletter']);
        $this->assertSame(0, DB::table('campaign_visits')->whereNotNull('user_id')->count());
    }

    public function test_excluded_accounts_remain_excluded_on_the_blog_host()
    {
        $post = $this->article();
        $user = Model::withoutEvents(function () { return create(User::class, ['email' => 'elena.dare@gmail.com']); });
        $this->actingAs($user, 'web');
        $this->get(route('posts.show', ['post' => $post, 'utm_source' => 'newsletter']))->assertOk();
        $this->assertDatabaseCount('campaign_visits', 0);
        $this->assertDatabaseCount('activity_events', 0);
    }
}
