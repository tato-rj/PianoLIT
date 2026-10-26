<?php
namespace Tests\Review;

use App\Providers\RouteServiceProvider;
use App\Http\Middleware\Logs\RecordWebLog;
use Illuminate\Routing\RouteCollection;

abstract class GamesTestCase extends ReviewTestCase
{
    private ?\App\User $gameAccount = null;

    protected function signInPlayer(): \App\User
    {
        $this->gameAccount ??= \Illuminate\Database\Eloquent\Model::withoutEvents(fn () => create(\App\User::class));
        $this->actingAs($this->gameAccount, 'web');

        return $this->gameAccount;
    }

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
