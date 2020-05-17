<?php

namespace App\Http\Middleware\Membership;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;

class VerifyStatus
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
            abort(403, 'You already have a membership account');
        
        return $next($request);
    }
}
