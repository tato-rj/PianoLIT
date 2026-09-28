<?php

namespace Tests\Review;

use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Routing\RouteCollection;

class ShopSubdomainTest extends ReviewTestCase
{
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
    public function test_shop_root_takes_priority_without_changing_existing_routes($baseUrl)
    {
        $this->useDomain($baseUrl);
        $host = parse_url($baseUrl, PHP_URL_HOST);
        $scheme = parse_url($baseUrl, PHP_URL_SCHEME);
        $shop = app('router')->getRoutes()->getByName('shop.home');

        $this->assertSame('shop.'.$host, $shop->getDomain());
        $this->assertContains('web', $shop->middleware());
        $this->assertNotContains('auth:web', $shop->middleware());
        $this->assertSame($scheme.'://shop.'.$host, route('shop.home'));

        foreach (['GET', 'HEAD'] as $method) {
            foreach ([$host => 'home', 'shop.'.$host => 'shop.home', 'my.'.$host => 'webapp.discover', 'admin.'.$host => 'admin.home'] as $domain => $name) {
                $matched = app('router')->getRoutes()->match(Request::create($scheme.'://'.$domain.'/', $method));
                $this->assertSame($name, $matched->getName());
            }
        }

        foreach (['/shop/validate-coupon' => 'shop.validate-coupon', '/ebooks' => 'ebooks.index', '/api/pieces/find' => 'api.pieces.find'] as $path => $name) {
            $matched = app('router')->getRoutes()->match(Request::create($baseUrl.$path));
            $this->assertSame($name, $matched->getName());
        }
    }

    /** @dataProvider domains */
    public function test_guests_see_the_public_site_styled_placeholder($baseUrl)
    {
        $this->useDomain($baseUrl);
        $this->get(route('shop.home'))->assertOk()
            ->assertViewIs('shop.coming-soon')
            ->assertSee('Coming up soon!')
            ->assertDontSee('<h1', false)
            ->assertSee('images/shop/coming-soon.webp', false)
            ->assertSee('href="'.$baseUrl.'"', false)
            ->assertSee('images/brand/app-icon.svg', false)
            ->assertSee('css/app.css', false)
            ->assertSee('Poppins', false)
            ->assertDontSee('webapp-layout', false);
        $this->assertGuest('web');
    }

    /** @dataProvider domains */
    public function test_main_site_links_are_absolute_even_without_a_configured_scheme($baseUrl)
    {
        $this->useDomain($baseUrl);
        config(['app.url' => parse_url($baseUrl, PHP_URL_HOST)]);
        $this->get(route('shop.home'))->assertOk()->assertSee('href="'.$baseUrl.'"', false);
    }

    public static function domains()
    {
        return [['https://pianolit.com'], ['http://pianolit.test']];
    }
}
