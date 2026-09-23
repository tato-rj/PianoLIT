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
        // The public web app uses session-authenticated web routes, never the
        // legacy mobile API's caller-supplied identity.
        if ($request->getHost() === 'my.'.parse_url(config('app.url'), PHP_URL_HOST)) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            if ($request->has('user_id'))
                auth()->login(User::findOrFail($request->user_id));   
        } catch (\Exception $e) {
            bugreport($e);
        }

        return $next($request);
    }
}
