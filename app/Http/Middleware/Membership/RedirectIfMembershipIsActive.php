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
        if (auth('web')->check() && ($membership = auth('web')->user()->membership)) {
            $source = $membership->source;
            // Payment issues need recovery on the existing subscription, not a
            // second checkout. Content access and billing ownership are separate.
            $existingStripe = $source instanceof \App\Billing\Sources\Stripe && !$source->isEnded()
                && !in_array($source->status, ['canceled', 'incomplete_expired'], true);
            if ($existingStripe || ($source instanceof \App\Billing\Sources\Apple && $source->isActive())) return redirect(route('webapp.membership.edit'));
        }

        return $next($request);
    }
}
