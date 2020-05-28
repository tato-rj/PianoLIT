<?php

namespace App\Http\Middleware\Membership;

use Closure;

class RedirectIfNotMember
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
        if (auth()->user()->isAuthorized() || $this->isFree($request))
            return $next($request);

        if (auth()->user()->membership()->exists() && auth()->user()->membership->source->isPaused())
            return redirect(route('webapp.membership.edit'));

        return redirect(route('webapp.membership.pricing'));
    }

    public function isFree($request)
    {
        $freepick = $request->route()->hasParameter('piece') && $request->route()->parameter('piece')->is_free;

        $featured = $request->route()->hasParameter('playlist') && $request->route()->parameter('playlist')->is_featured;

        return $freepick || $featured;
    }
}
