<?php

namespace App\Http\Middleware;

use Closure;

class DevOnly
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $route)
    {
        if (production())
            return redirect(route($route));

        return $next($request);
    }
}
