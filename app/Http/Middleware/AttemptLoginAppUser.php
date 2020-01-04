<?php

namespace App\Http\Middleware;

use Closure;
use App\User;

class AttemptLoginAppUser
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
        try {
            auth()->login(User::findOrFail($request->user_id));   
        } catch (\Exception $e) {
            \Bugsnag::notifyException($e);
        }

        return $next($request);
    }
}
