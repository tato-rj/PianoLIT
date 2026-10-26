<?php
namespace Tests\Review;

use App\Providers\RouteServiceProvider;
use App\Http\Middleware\Logs\RecordWebLog;
use Illuminate\Routing\RouteCollection;

abstract class GamesTestCase extends ReviewTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(RecordWebLog::class);
        config(['app.url' => 'http://localhost', 'app.short_url' => 'localhost']);
        app('router')->setRoutes(new RouteCollection);
        (new RouteServiceProvider($this->app))->map();
        $routes = app('router')->getRoutes(); $routes->refreshNameLookups();
        app('url')->setRoutes($routes);
        $this->withExceptionHandling();
        \Illuminate\Support\Facades\Redis::shouldReceive('get')->andReturn(null);
    }

    protected function prepareUrlForRequest($uri)
    {
        return strpos($uri, 'http') === 0 ? $uri : parent::prepareUrlForRequest($uri);
    }
}