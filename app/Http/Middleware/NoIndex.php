<?php

namespace App\Http\Middleware;

use Closure;

class NoIndex
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex');

        return $response;
    }
}
