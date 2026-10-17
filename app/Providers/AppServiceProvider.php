<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Collection;
use Laravel\Scout\Builder;
use App\Shop\Contract\Merchandise;
use App\Shop\{eBook, eScore};
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->scoped(\App\Services\WebApp\AccessPolicy::class);
        \View::composer('*', function($view) {
            $view->with(['full' => ! request()->has('bodyonly')]);
        });

        \View::composer([
            'webapp.discover.index', 
            'webapp.explore.index', 
            'webapp.highlights.pieces', 
            'webapp.playlists.index', 
            'webapp.playlists.show', 
            'webapp.user.my-pieces.index',
            'webapp.user.my-pieces.favorites.folders.show',
            'webapp.search.results',
            'webapp.piece.index',
            'webapp.piece.options.similar',
            'webapp.piece.options.collection',
            'home.index'
        ], function($view) {
            // Browsing stays public; piece media has a separate subscriber check below.
            $view->with(['hasFullAccess' => strpos($view->name(), 'webapp.') === 0
                || (auth()->check() && auth()->user()->isAuthorized())]);
        });

        \View::composer([
            'webapp.piece.index',
            'webapp.piece.components.audio',
            'webapp.piece.components.video.element',
            'webapp.piece.components.score-editor',
        ], function ($view) {
            $piece = $view->getData()['piece'];
            $user = auth('web')->user();
            $policy = app(\App\Services\WebApp\AccessPolicy::class);
            $view->with('hasMediaAccess', $piece->hasWebMediaAccess($user));
            $view->with('hasPerformanceAccess', $piece->hasWebMediaAccess($user, 'performance'));
            $view->with('hasSynthesiaAccess', $piece->hasWebMediaAccess($user, 'synthesia'));
            $view->with('hasAudioAccess', $piece->hasWebMediaAccess($user, 'audio'));
            if (isset($view->getData()['tutorial'])) {
                $tutorial = $view->getData()['tutorial'];
                $content = stripos($tutorial->type, 'performance') !== false ? 'performance'
                    : (stripos($tutorial->type, 'synthesia') !== false ? 'synthesia' : 'tutorial');
                $view->with('hasVideoAccess', $piece->hasWebMediaAccess($user, $content));
            }
            $view->with('hasScoreExportAccess', $policy->available('exports', $user, $piece));
            $view->with('hasAnnotationAccess', $policy->available('annotations', $user, $piece));
            $view->with('previewSeconds', \App\Services\WebApp\AccessPolicy::MEDIA_SECONDS);
            $view->with('synthesiaPreviewSeconds', $policy->mediaLimit('synthesia', $user, $piece));
            $view->with('audioPreviewSeconds', $policy->mediaLimit('audio', $user, $piece));
            $view->with('videoPreviewSeconds', $policy->mediaLimit($content ?? 'performance', $user, $piece));
        });

        \View::composer('components.display.ads', function($view) {
            $view->with(['ad' => [
                'ebook' => \App\Shop\eBook::published()->latest()->first(),
                'escore' => \App\Shop\eScore::published()->latest()->first(),
                'crashcourse' => \App\CrashCourse\CrashCourse::published()->latest()->first()
            ]]);
        });

        \View::composer('components.popups.crashcourse', function($view) {
            $highlightedCrashcourse = \Cache::remember('highlightedCrashcourse', days(7), function() {
                return \App\CrashCourse\CrashCourse::published()->first();
            });

            $view->with(['highlightedCrashcourse' => $highlightedCrashcourse]);
        });

        \View::composer('components.popups.gift', function($view) {
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
        // Laravel 8's send event has view data but no mailable instance. Attach
        // only our fixed delivery key; preview and marketing mail remain independent.
        \Illuminate\Mail\Mailable::buildViewDataUsing(function ($mail) {
            $key = app(\App\Mail\AutomaticEmailSettings::class)->mailableKey($mail);
            return $key ? ['pianolit_automatic_email' => $key] : [];
        });

        Paginator::useBootstrap();

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

        Collection::macro('notNull', function() {
            return $this->filter(function($item) {
                return !is_null($item);
            })->values();
        });

        Collection::macro('flattenWithKeys', function() {
            return $this->mapWithKeys(function($a) {
                return $a;
            });
        });
    }
}
