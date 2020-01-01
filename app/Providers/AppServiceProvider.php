<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Collection;
use Laravel\Scout\Builder;

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
            $view->with(['full' => ! request()->has('bodyonly')]);
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

        Builder::macro('options', function($params) {
            $this->callback = function($algolia, $query, $options) use ($params) {
                return $algolia->search($query, $params);
            };

            return $this;
        });

        Collection::macro('scoutRelevance', function() {
            return $this->sortByDesc(function($piece, $key) {
                return $piece->scoutMetadata()['_rankingInfo']['words'];
            })->values();
        });

        Collection::macro('favorited', function($bool, $user_id) {
            return $this->filter(function($piece) use ($bool, $user_id) {
                return $bool ? $piece->isFavorited($user_id) : ! $piece->isFavorited($user_id);
            });
        });
    }
}
