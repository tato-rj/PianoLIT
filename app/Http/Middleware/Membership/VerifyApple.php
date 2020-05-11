<?php

namespace App\Http\Middleware\Membership;

use Closure;
use App\Billing\Membership;
use App\Billing\Sources\Apple;
use App\User;
use Illuminate\Auth\Access\AuthorizationException;

class VerifyApple
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
        if (Membership::hasSourceFor(Apple::class, User::findOrFail($request->user_id)))
            throw new AuthorizationException('You already have an expired or inactive membership account with Apple');

        return $next($request);
    }
}
