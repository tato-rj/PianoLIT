<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Collection;
use Laravel\Scout\Builder;
use App\Shop\Contract\Merchandise;
use App\Shop\{eBook, eScore};

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

        \View::composer([
            'webapp.discover.index', 
            'webapp.explore.index', 
            'webapp.playlists.index', 
            'webapp.playlists.show', 
            'webapp.user.my-pieces.index',
            'webapp.search.results',
            'webapp.piece.options.similar',
            'webapp.piece.options.collection',
            'home.index'
        ], function($view) {
            $view->with(['hasFullAccess' => auth()->check() ? auth()->user()->isAuthorized() : false]);
        });

        \View::composer('components.display.ads', function($view) {
            $view->with(['ad' => [
                'ebook' => \App\Shop\eBook::published()->latest()->first(),
                'escore' => \App\Shop\eScore::published()->latest()->first(),
                'crashcourse' => []
            ]]);
        });

        \View::composer('components.popups.crashcourse', function($view) {
            $highlightedCrashcourse = \Cache::remember('highlightedCrashcourse', days(7), function() {
                return \App\CrashCourse\CrashCourse::published()->first();
            });

            $view->with(['highlightedCrashcourse' => $highlightedCrashcourse]);
        });

        \View::composer('components.popups.subscription', function($view) {
            $gift = \Cache::remember('infographs.gift', minutes(2), function() {
                return \App\Infograph\Infograph::gifts()->inRandomOrder()->first();
            });

            $view->with(['gift' => $gift]);
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

        \Blade::if('inarray', function ($needle, $array) {
            return in_array($needle, $array);
        });

        \Blade::if('editor', function () {
            return auth()->guard('admin')->user()->role == 'editor';
        });

        \Blade::if('confirmed', function (bool $state = true) {
            if (! auth()->guard('web')->check())
                return false;

            return $state ? auth()->user()->confirmed : ! auth()->user()->confirmed;
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

        Collection::macro('insertBefore', function ($index, $element) {
            if ($index > 0)
                return $this->splice($index - 1, 0, $element);

            return $this;
        });

        Collection::macro('insertAfter', function ($index, $element) {
            return $this->splice($index + 1, 0, $element);
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
