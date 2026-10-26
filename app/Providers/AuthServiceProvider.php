<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Piece' => 'App\Policies\PiecePolicy',
        'App\Composer' => 'App\Policies\ComposerPolicy',
        'App\Tag' => 'App\Policies\TagPolicy',
        'App\Topic' => 'App\Policies\TopicPolicy',
        'App\Admin' => 'App\Policies\AdminPolicy',
        'App\Payments\Membership' => 'App\Policies\MembershipPolicy',
        'App\User' => 'App\Policies\UserPolicy',
        'App\TutorialRequest' => 'App\Policies\TutorialRequestPolicy',
        'App\Review' => 'App\Policies\ReviewPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        \Auth::extend('pianolit-v2', function ($app, $name, $config) {
            $guard = new \App\Auth\ApiV2\BearerGuard(function ($request) {
                return app(\App\Auth\ApiV2\Sessions::class)->authenticate($request);
            }, $app['request'], $app['auth']->createUserProvider($config['provider']));
            $app->refresh('request', $guard, 'setRequest');
            return $guard;
        });

        // Keep the original middleware identity so Apple's stateless POST keeps
        // its existing withoutMiddleware boundary.
        $this->app->singleton(\Illuminate\Session\Middleware\StartSession::class, function ($app) {
            return new \App\Http\Middleware\StartWebSession($app['session'], function () use ($app) { return $app['cache']; });
        });

        \Auth::extend('shared-web-session', function ($app, $name, $config) {
            $guard = new \App\Auth\SharedSessionGuard($name, $app['auth']->createUserProvider($config['provider']), $app['session.store']);
            $guard->setCookieJar($app['cookie']);
            $guard->setDispatcher($app['events']);
            $guard->setRequest($app->refresh('request', $guard, 'setRequest'));
            return $guard;
        });

        \Gate::before(function($user) {
            if ($user->role == 'manager')
                return true;
        });
    }
}
