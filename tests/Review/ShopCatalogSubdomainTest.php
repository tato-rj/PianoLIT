<?php

namespace Tests\Review;

use App\{Admin, User};
use App\Shop\{eBook, eBookTopic, eScore, eScoreTopic};
use App\Providers\RouteServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\{DB, Mail, Redis};
use Stevebauman\Location\Facades\Location;

class ShopCatalogSubdomainTest extends ReviewTestCase
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

    public static function catalogs()
    {
        return [['ebooks', eBook::class, eBookTopic::class], ['escores', eScore::class, eScoreTopic::class]];
    }

    private function product($class, array $attributes = [])
    {
        return Model::withoutEvents(function () use ($class, $attributes) {
            return create($class, $attributes + ['title' => 'Catalog example', 'published_at' => now(), 'price' => 15, 'discount' => 0, 'previews' => '', 'mockup_path' => 'catalog/example.jpg']);
        });
    }

    /** @dataProvider domains */
    public function test_all_catalog_route_names_generate_shop_urls_from_every_existing_host($baseUrl)
    {
        $this->useDomain($baseUrl);
        $host = parse_url($baseUrl, PHP_URL_HOST);
        $scheme = parse_url($baseUrl, PHP_URL_SCHEME);
        foreach (['', 'blog.', 'shop.', 'my.', 'admin.'] as $prefix) {
            $request = Request::create($scheme.'://'.$prefix.$host);
            $this->app->instance('request', $request);
            app('url')->setRequest($request);
            foreach (['ebooks' => 'ebook', 'escores' => 'escore'] as $catalog => $parameter) {
                foreach (['index' => ['', []], 'show' => ['/example-product', [$parameter => 'example-product']], 'topic' => ['/topics/theory', ['topic' => 'theory']], 'checkout' => ['/example-product/checkout', [$parameter => 'example-product']]] as $action => [$path, $parameters]) {
                    $name = $catalog.'.'.$action;
                    $url = route($name, $parameters);
                    $this->assertSame($scheme.'://shop.'.$host.'/'.$catalog.$path, $url);
                    $this->assertSame('/'.$catalog.$path, route($name, $parameters, false));
                    foreach (['GET', 'HEAD'] as $method) {
                        $route = app('router')->getRoutes()->match(Request::create($url, $method));
                        $this->assertSame($name, $route->getName());
                        $this->assertSame('shop.'.$host, $route->getDomain());
                        $this->assertContains('web', $route->middleware());
                        $this->assertContains('log.web', $route->middleware());
                        $this->assertContains('location.update', $route->middleware());
                        $this->assertSame($action === 'checkout', in_array('auth:web', $route->middleware(), true));
                    }
                }
            }
        }
        foreach (['ebooks', 'escores'] as $catalog) {
            $this->assertSame('admin.'.$catalog.'.index', app('router')->getRoutes()->match(Request::create($scheme.'://admin.'.$host.'/'.$catalog))->getName());
        }
    }

    public static function legacyUrls()
    {
        $urls = [];
        foreach (['ebooks', 'escores'] as $catalog) {
            foreach (['', '/', '?topics%5B0%5D=theory&page=2', '/example-product', '/example-product/checkout?coupon=A%20%26%20B', '/topics/theory?utm_source=newsletter', '/missing-product?next=%2Febooks%2Fother'] as $suffix) {
                $urls[] = ['/'.$catalog.$suffix];
            }
        }
        return $urls;
    }

    /** @dataProvider legacyUrls */
    public function test_legacy_urls_redirect_with_their_full_path_and_raw_query($path)
    {
        $this->useDomain('http://pianolit.test');
        foreach (['pianolit.test', 'www.pianolit.test', 'my.pianolit.test'] as $host) {
            foreach (['GET', 'HEAD'] as $method) {
                $this->call($method, 'http://'.$host.$path)->assertStatus(301)->assertRedirect('http://shop.pianolit.test'.$path);
            }
        }
    }

    /** @dataProvider catalogs */
    public function test_catalogs_products_filters_and_checkout_links_render_on_shop($catalog, $class, $topicClass)
    {
        $product = $this->product($class);
        $topic = create($topicClass, ['name' => 'Theory', 'slug' => 'theory']);
        $product->topics()->attach($topic);
        $this->product($class, ['title' => 'Unpublished catalog entry', 'published_at' => null]);
        $this->get(route($catalog.'.index'))->assertOk()->assertSee($product->title)->assertDontSee('Unpublished catalog entry')
            ->assertSee('href="http://shop.localhost/'.$catalog.'/'.$product->slug.'"', false);
        $this->get(route($catalog.'.index', ['topics' => ['theory']]))->assertOk()->assertSee($product->title);
        $this->get(route($catalog.'.index', ['topics' => ['absent']]))->assertOk()->assertDontSee($product->title);
        $response = $this->get($product->showRoute())->assertOk()->assertSee($product->title)
            ->assertSee('href="http://shop.localhost/'.$catalog.'/'.$product->slug.'/checkout"', false)
            ->assertSee('<link rel="canonical" href="'.$product->showRoute().'"', false)
            ->assertSee('href="http://shop.localhost/login"', false)
            ->assertSee('href="http://localhost/support"', false)
            ->assertSee('action="'.$product->reviewRoute().'"', false);
        $this->assertStringStartsWith('http://shop.localhost/reviews/', $product->reviewRoute());
        $this->assertStringStartsWith('http://shop.localhost/shop/', $product->purchaseRoute());
        $this->assertSame('http://shop.localhost/'.$catalog, $product->indexRoute());
        $this->assertSame('http://localhost', route('home'));
        $this->assertSame('http://blog.localhost', route('posts.index'));
        $this->get(route('shop.home'))->assertOk()->assertSee('All products')->assertSee($product->title)->assertDontSee('Unpublished catalog entry');
        Mail::assertNothingSent();
        $this->assertDatabaseCount('purchases', 0);
    }

    /** @dataProvider catalogs */
    public function test_checkout_keeps_the_web_guard_same_host_forms_and_csrf($catalog, $class)
    {
        $product = $this->product($class);
        $checkout = $product->checkoutRoute();
        $this->withExceptionHandling()->get($checkout)->assertRedirect('http://shop.localhost/login');
        $this->getJson($checkout)->assertUnauthorized();
        $user = Model::withoutEvents(function () { return create(User::class); });
        $user->customer()->create(['stripe_id' => 'cus_shop_fixture', 'card_last_four' => '4242', 'card_brand' => 'visa']);
        $this->actingAs($user, 'web');
        $response = $this->get($checkout)->assertOk();
        $response->assertSee('action="'.$product->purchaseRoute().'"', false)
            ->assertSee('data-url="http://shop.localhost/shop/validate-coupon"', false)
            ->assertSee('action="http://shop.localhost/shop/payment-method"', false)
            ->assertSee('href="http://localhost/terms-of-service"', false);
        $this->assertStringStartsWith('http://shop.localhost/shop/', $product->purchaseRoute());
        $this->app['env'] = 'review-csrf';
        $this->withSession(['_token' => 'shop-csrf'])->postJson($product->purchaseRoute())->assertStatus(419);
        $this->deleteJson('http://shop.localhost/shop/payment-method')->assertStatus(419);
        $this->assertTrue($user->customer->fresh()->hasCard());
        Mail::assertNothingSent();
        $this->assertDatabaseCount('purchases', 0);
    }

    /** @dataProvider catalogs */
    public function test_complete_category_grid_stays_on_shop_and_unknown_paths_do_not_redirect_to_themselves($catalog, $class)
    {
        for ($i = 0; $i < 13; $i++) $this->product($class, ['title' => 'Catalog item '.$i]);
        $this->get(route($catalog.'.index'))->assertOk()
            ->assertSee('Catalog item 0')->assertSee('Catalog item 12')->assertDontSee('shop-pagination');
        $this->get(route($catalog.'.index', ['page' => 2]))->assertOk();
        $this->withExceptionHandling()->get('http://shop.localhost/'.$catalog.'/missing/unrecognized')->assertNotFound();
        $this->get('http://shop.localhost/'.$catalog.'/missing-product')->assertNotFound();
    }

    /** @dataProvider domains */
    public function test_shop_navigation_remains_absolute_without_a_configured_scheme($baseUrl)
    {
        $this->useDomain($baseUrl);
        $host = parse_url($baseUrl, PHP_URL_HOST);
        config(['app.url' => $host]);
        $shop = parse_url($baseUrl, PHP_URL_SCHEME).'://shop.'.$host;
        $this->get(route('ebooks.index'))->assertOk()
            ->assertSee('href="'.$baseUrl.'"', false)
            ->assertSee('href="'.$shop.'/login"', false);
        $this->assertSame($shop.'/ebooks', route('ebooks.index'));
        $this->assertSame($shop.'/escores', route('escores.index'));
        $this->assertSame($baseUrl.'/privacy-policy', route('privacy'));
        $this->assertSame($shop.'/subscriptions/modal', route('subscriptions.modal'));
        $this->assertSame($shop.'/shop/validate-coupon', route('shop.validate-coupon'));
    }

    /** @dataProvider catalogs */
    public function test_campaign_redirects_count_one_anonymous_shop_arrival($catalog)
    {
        $path = '/'.$catalog.'?utm_source=newsletter&utm_campaign=store';
        $this->get('http://localhost'.$path)->assertStatus(301)->assertRedirect('http://shop.localhost'.$path);
        $this->assertDatabaseCount('campaign_visits', 0);
        $this->get('http://shop.localhost'.$path)->assertOk()->assertDontSee('Campaign link measurement');
        $this->get('http://shop.localhost'.$path)->assertOk();
        $this->assertDatabaseCount('campaign_visits', 1);
        $this->assertDatabaseHas('campaign_visits', ['landing_url' => 'http://shop.localhost/'.$catalog, 'utm_source' => 'newsletter', 'user_id' => null, 'consented_at' => null]);
        $this->assertSame(0, DB::table('campaign_visits')->whereNotNull('user_id')->count());
    }
}
