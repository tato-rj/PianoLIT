<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Infograph;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        \View::composer('*', function($view) {
            $view->with('gift', Infograph::gifts()->inRandomOrder()->first());
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        \Blade::if('manager', function () {
            return auth()->guard('admin')->user()->role == 'manager';
        });

        \Blade::if('editor', function () {
            return auth()->guard('admin')->user()->role == 'editor';
        });

        \Blade::if('created', function ($model) {
            return auth()->guard('admin')->user()->id == $model->creator_id || auth()->guard('admin')->user()->role == 'manager';
        });

        \Blade::if('env', function ($environment) {
            return app()->environment($environment);
        });
    }
}
