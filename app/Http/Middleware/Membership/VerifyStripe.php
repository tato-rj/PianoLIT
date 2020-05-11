<?php

namespace App\Http\Middleware\Membership;

use Closure;
use App\Billing\Membership;
use App\Billing\Sources\Stripe;
use Illuminate\Auth\Access\AuthorizationException;

class VerifyStripe
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
        if (Membership::hasSourceFor(Stripe::class, auth()->user()) && ! auth()->user()->membership->source->isCanceled())
            return redirect(route('webapp.membership.edit'));

        return $next($request);
    }
}
