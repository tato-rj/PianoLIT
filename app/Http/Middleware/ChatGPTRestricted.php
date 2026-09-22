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
        $expected = config('services.chatgpt.token');
        $provided = $request->bearerToken();

        if (! is_string($expected) || $expected === '' || ! is_string($provided) || ! hash_equals($expected, $provided))
            return response()->json(['message' => 'Unauthorized'], 401);

        return $next($request);
    }
}
