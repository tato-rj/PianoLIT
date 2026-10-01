<?php

namespace Tests\Review;

use App\{Admin, User};
use App\Providers\RouteServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Storage;

class AdminSubdomainTest extends ReviewTestCase
{
    protected function prepareUrlForRequest($uri)
    {
        // Laravel's test helper trims trailing slashes from absolute request URLs.
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

    /** @dataProvider domains */
    public function test_every_admin_route_uses_the_subdomain_and_resolves_before_public_routes($baseUrl)
    {
        $this->useDomain($baseUrl);
        $domain = 'admin.'.parse_url($baseUrl, PHP_URL_HOST);
        $origin = parse_url($baseUrl, PHP_URL_SCHEME).'://'.$domain;
        $count = 0;

        foreach (app('router')->getRoutes() as $route) {
            if (strpos($route->getName() ?? '', 'admin.') !== 0) {
                continue;
            }

            $count++;
            $this->assertSame($domain, $route->getDomain(), $route->getName());
            $this->assertFalse(strpos($route->uri(), 'admin/') === 0, $route->getName());
            $parameters = array_fill_keys($route->parameterNames(), '1');
            $url = route($route->getName(), $parameters);
            $this->assertSame($domain, parse_url($url, PHP_URL_HOST));
            foreach ($route->methods() as $method) {
                $matched = app('router')->getRoutes()->match(Request::create($url, $method));
                $this->assertSame($route->getName(), $matched->getName(), $method.' '.$url);
            }
            $this->assertContains('web', $route->middleware());
            $this->assertContains(strpos($route->getName(), 'admin.login.') === 0 ? 'guest:admin' : 'auth:admin', $route->middleware());
        }

        $this->assertGreaterThan(100, $count);
        $this->assertSame($origin, route('admin.home'));
        $this->assertSame($origin.'/login', route('admin.login.show'));
        foreach (['/' => 'home', '/login' => 'login', '/blog' => 'posts.index'] as $path => $name) {
            $matched = app('router')->getRoutes()->match(Request::create($baseUrl.$path));
            $this->assertSame($name, $matched->getName());
        }
        $webapp = parse_url($baseUrl, PHP_URL_SCHEME).'://my.'.parse_url($baseUrl, PHP_URL_HOST);
        $this->assertSame('webapp.discover', app('router')->getRoutes()->match(Request::create($webapp))->getName());
        $this->assertSame('api.pieces.find', app('router')->getRoutes()->match(Request::create($baseUrl.'/api/pieces/find'))->getName());
    }

    public static function domains()
    {
        return [['https://pianolit.com'], ['http://pianolit.test']];
    }

    /** @dataProvider domains */
    public function test_public_preview_links_generated_on_admin_use_the_public_site($baseUrl)
    {
        $this->useDomain($baseUrl);
        $origin = parse_url($baseUrl, PHP_URL_SCHEME).'://admin.'.parse_url($baseUrl, PHP_URL_HOST);
        $request = Request::create($origin.'/blog/1');
        $this->app->instance('request', $request);
        app('url')->setRequest($request);
        app('url')->forceRootUrl(null);

        foreach (['posts.show' => '/blog/preview-post', 'ebooks.show' => '/ebooks/preview-post', 'escores.show' => '/escores/preview-post', 'crashcourses.show' => '/crashcourses/preview-post'] as $name => $path) {
            $this->assertSame($baseUrl.$path, route($name, 'preview-post'));
            $this->assertSame($path, route($name, 'preview-post', false));
        }
        $this->assertSame($baseUrl, route('home'));
        $this->assertSame($origin.'/pieces/1/edit', route('admin.pieces.edit', 1));
        $this->assertSame($origin.'/impersonate/1', route('impersonate', 1));
        $this->assertSame($origin.'/redis/update/api', route('redis.update'));

        $request = Request::create($baseUrl);
        $this->app->instance('request', $request);
        app('url')->setRequest($request);
        $this->assertSame($baseUrl.'/blog/preview-post', route('posts.show', 'preview-post'));
    }

    public function test_authenticated_piece_creation_renders_the_new_ajax_urls()
    {
        $this->actingAs(create(Admin::class), 'admin');
        $response = $this->get(route('admin.pieces.create'))->assertOk();
        $response->assertSee(json_encode(route('admin.posts.upload-image')), false);
        foreach (['single-lookup', 'multi-lookup', 'validate-name'] as $name) {
            $response->assertSee(json_encode(route('admin.pieces.'.$name)), false);
        }
        $response->assertDontSee('/admin/pieces/', false);
        $response->assertSee('class="offcanvas offcanvas-end" id="notifications-panel"', false)
            ->assertSee('data-bs-toggle="offcanvas" data-bs-target="#notifications-panel"', false)
            ->assertSee('aria-labelledby="notifications-panel-title"', false)
            ->assertSee('data-bs-dismiss="offcanvas"', false)
            ->assertDontSee('fixed-panel', false);
    }

    /** @dataProvider legacyUrls */
    public function test_old_admin_links_redirect_with_the_original_path_and_query($oldPath, $newPath)
    {
        $this->useDomain('http://pianolit.test');
        foreach (['pianolit.test', 'www.pianolit.test', 'admin.pianolit.test'] as $host) {
            $this->get('http://'.$host.$oldPath)->assertStatus(301)
                ->assertRedirect('http://admin.pianolit.test'.$newPath);
        }
    }

    public static function legacyUrls()
    {
        return [
            ['/admin', ''],
            ['/admin/', '/'],
            ['/admin?tab=users', '?tab=users'],
            ['/admin/login', '/login'],
            ['/admin/pieces/42/edit?search=Bach%20%26%20Mozart&page=2', '/pieces/42/edit?search=Bach%20%26%20Mozart&page=2'],
            ['/admin/blog/42?next=%2Fadmin%2Fpieces', '/blog/42?next=%2Fadmin%2Fpieces'],
        ];
    }

    public function test_guests_and_regular_web_users_must_use_the_admin_login()
    {
        $this->withExceptionHandling();
        foreach (['admin.home', 'admin.pieces.index'] as $name) {
            $this->get(route($name))->assertRedirect(route('admin.login.show'));
            $this->getJson(route($name))->assertUnauthorized();
        }
        $user = Model::withoutEvents(function () { return create(User::class); });
        $this->actingAs($user, 'web');
        $this->get(route('admin.home'))->assertRedirect(route('admin.login.show'));
        $this->get(route('admin.login.show'))->assertOk()->assertSee(route('admin.login.submit'), false);
        $this->assertGuest('admin');
    }

    public function test_admin_login_retains_the_intended_page_and_logout_returns_to_admin()
    {
        $admin = create(Admin::class, ['password' => bcrypt('review-password')]);
        $target = route('admin.pieces.index', ['page' => 2]);
        $this->withExceptionHandling()->get($target)->assertRedirect(route('admin.login.show'));
        $this->post(route('admin.login.submit'), ['email' => $admin->email, 'password' => 'review-password'])
            ->assertRedirect($target);
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->get(route('admin.login.show'))->assertRedirect(route('admin.home'));
        $this->post(route('admin.logout'))->assertRedirect(route('admin.home'));
        $this->assertGuest('admin');
        $this->get(route('admin.home'))->assertRedirect(route('admin.login.show'));
    }

    public function test_failed_admin_login_returns_to_the_subdomain_login()
    {
        $this->post(route('admin.login.submit'), ['email' => 'missing@example.test', 'password' => 'wrong'])
            ->assertRedirect(route('admin.login.show'))->assertSessionHasErrors('email');
        $this->assertGuest('admin');
    }

    public function test_editor_uploads_require_the_session_csrf_token_at_the_new_url()
    {
        Storage::fake('public');
        $this->actingAs(create(Admin::class), 'admin');
        $this->app['env'] = 'review-csrf';
        $this->withExceptionHandling()->withSession(['_token' => 'review-csrf']);
        $this->postJson(route('admin.posts.upload-image'))->assertStatus(419);
        $response = $this->withHeader('X-CSRF-TOKEN', 'review-csrf')->post(route('admin.posts.upload-image'), [
            'file' => UploadedFile::fake()->create('score.png', 1, 'image/png'),
        ])->assertOk();
        $this->assertStringContainsString('/storage/blog/content_images/', $response->json('location'));
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_impersonation_returns_to_the_public_website_from_the_admin_host()
    {
        $admin = create(Admin::class);
        $user = Model::withoutEvents(function () { return create(User::class); });
        $this->actingAs($admin, 'admin');
        $this->get('http://admin.localhost/impersonate/'.$user->id)->assertRedirect(config('app.url'));
        $this->assertAuthenticatedAs($user, 'web');
    }
}
