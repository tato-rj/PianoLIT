<?php

namespace App\Http\Middleware;

use Closure;

class CheckForMembership
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
        if (auth()->user()->isAuthorized() || $this->freepick($request))
            return $next($request);

        abort(402, 'Members only');
    }

    public function freepick($request)
    {
        return $request->route()->hasParameter('piece') && $request->route()->parameter('piece')->is_free;
    }
}
