<?php

namespace App\Http\Middleware\ApiV2;

use Closure;
use Illuminate\Auth\AuthenticationException;

class Authenticate
{
    public function handle($request, Closure $next)
    {
        $guard = auth('api_v2');
        $guard->setRequest($request);
        if (!$guard->user()) throw new AuthenticationException('Unauthenticated.', ['api_v2']);
        // Do not change the default guard or log into the legacy web identity.
        $request->setUserResolver(function ($name = null) { return auth($name ?: 'api_v2')->user(); });
        return $next($request);
    }
}
