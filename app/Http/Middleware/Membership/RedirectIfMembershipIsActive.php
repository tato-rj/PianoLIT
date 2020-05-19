<?php

namespace App\Http\Middleware\Membership;

use Closure;

class RedirectIfMembershipIsActive
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
        if (auth()->user()->membership()->exists() && (auth()->user()->isAuthorized() || auth()->user()->membership->source->isPaused()))
            return redirect(route('webapp.membership.edit'));

        return $next($request);
    }
}
