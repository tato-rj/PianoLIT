<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ChatGPTRestricted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->bearerToken() != env('CHATGPT_TOKEN'))
            response()->json(['message' => 'Unauthorized'], 401);

        return $next($request);
    }
}
