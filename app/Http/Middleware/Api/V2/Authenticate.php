<?php

namespace App\Http\Middleware\Api\V2;

use App\Services\Api\V2\{Response, Tokens};
use Closure;

class Authenticate
{
    public function handle($request, Closure $next)
    {
        $user = app(Tokens::class)->resolve($request->bearerToken());
        if (!$user) return Response::error('unauthenticated', 'Please sign in again.', 401);
        // Do not populate the browser guard or inspect submitted user_id/cookies.
        $request->setUserResolver(function () use ($user) { return $user; });
        return $next($request);
    }
}
