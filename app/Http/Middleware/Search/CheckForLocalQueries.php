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
        if (Tag::name($request->search)->exists())
            $request['model'] = Tag::class;

        if (Composer::name($request->search)->exists())
            $request['model'] = Composer::class;

        return $next($request);
    }
}
