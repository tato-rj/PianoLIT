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
            if ($request->has('user_id'))
                auth()->login(User::findOrFail($request->user_id));   
        } catch (\Exception $e) {
            bugreport($e);
        }

        return $next($request);
    }
}
