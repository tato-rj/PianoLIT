<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        //

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapWebAppRoutes();

        $this->mapApiRoutes();

        $this->mapAuthRoutes();

        $this->mapWebRoutes();

        $this->mapAdminRoutes();

        $this->mapMailRoutes();

        $this->mapUserRoutes();

        $this->mapWebhooksRoutes();

        $this->mapRedirectsRoutes();

        $this->mapRedisRoutes();
    }

    /**
     * Define the "user" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapWebAppRoutes()
    {
        Route::domain('my.'.config('app.short_url'))
             ->middleware(['web', 'auth:web', 'log.webapp'])
             ->name('webapp.')
             ->namespace($this->namespace)
             ->group(function() {
                $this->getFolder('routes/webapp');
             });
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware(['web', 'log.web'])
             ->namespace($this->namespace)
             ->group(function() {
                $this->getFolder('routes/web');
             });
    }

    /**
     * Define the "auth" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapAuthRoutes()
    {
        Route::middleware('web')
             ->namespace($this->namespace)
             ->group(base_path('routes/auth.php'));
    }

    /**
     * Define the "admin" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapAdminRoutes()
    {
        Route::middleware(['web', 'auth:admin'])
             ->prefix('admin')
             ->name('admin.')
             ->namespace($this->namespace)
             ->group(function() {
                $this->getFolder('routes/admin');
             });
    }

    /**
     * Define the "admin" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapMailRoutes()
    {
        Route::middleware(['web'])
             ->prefix('email-preview')
             ->name('email-preview.')
             ->namespace($this->namespace)
             ->group(base_path('routes/mail.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        Route::prefix('api')
             ->name('api.')
             ->middleware('api')
             ->namespace($this->namespace)
             ->group(function() {
                $this->getFolder('routes/api');
             });
    }

    /**
     * Define the "user" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapUserRoutes()
    {
        Route::prefix('my-profile')
             ->middleware(['web', 'auth:web', 'log.web'])
             ->name('users.')
             ->namespace($this->namespace)
             ->group(base_path('routes/user.php'));
    }

    /**
     * Define the "user" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapWebhooksRoutes()
    {
        Route::prefix('webhooks')
            ->name('webhooks.')
            ->namespace($this->namespace)
            ->group(base_path('routes/webhooks.php'));
    }

    /**
     * Define the "user" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapRedirectsRoutes()
    {
        Route::namespace($this->namespace)
             ->group(base_path('routes/redirects.php'));
    }

    /**
     * Define the "user" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapRedisRoutes()
    {
        Route::middleware(['web', 'auth:admin'])
             ->prefix('redis')
             ->name('redis.')
             ->namespace($this->namespace)
             ->group(base_path('routes/redis.php'));
    }

    public function getFolder($path)
    {
        foreach (dirToArray($path) as $route) {
            require base_path($path . '/' . $route);
        }
    }
}
