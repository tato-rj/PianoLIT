<?php

namespace App\Http\Middleware\Search;

use Closure;
use App\Tag;

class CheckForExactMatches
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
            $request->search = $this->addQuotes($request->search);

        return $next($request);
    }

    public function addQuotes($string)
    {
        return '"'.$string.'"';
    }
}
