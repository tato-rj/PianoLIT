<?php

namespace App\Http\Middleware\Membership;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use App\User;

class OneMembershipSourcePerUser
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
        $user = auth()->check() ? auth()->user() : User::findOrFail($request->user_id);

        if ($user->membership()->exists())
            throw new AuthorizationException('You already have a membership associated with this account');
            
        return $next($request);
    }
}
