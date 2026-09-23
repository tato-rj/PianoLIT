<?php

namespace App\Http\Middleware\Search;

use Closure;
use App\{Tag, Composer};

class CheckForLocalQueries
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->routeIs('webapp.*')) {
            if ($request->routeIs('webapp.search.results') && ! auth('web')->check()
                && filter_var($request->page, FILTER_VALIDATE_INT) > 1) return $next($request);
            if (! is_string($request->search) || strlen($request->search) <= 2) return $next($request);

            // Composer matches have always taken precedence over tag matches.
            $match = Composer::without('country')->select('id')->name($request->search)->first()
                ?? Tag::select('id')->name($request->search)->first();
            if ($match) {
                $request['model'] = get_class($match);
                $request->attributes->set('webapp_search_match', $match);
            }
            return $next($request);
        }

        if (Tag::name($request->search)->exists())
            $request['model'] = Tag::class;

        if (Composer::name($request->search)->exists())
            $request['model'] = Composer::class;

        return $next($request);
    }
}
