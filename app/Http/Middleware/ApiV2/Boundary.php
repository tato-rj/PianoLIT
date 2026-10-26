<?php

namespace App\Http\Middleware\ApiV2;

use App\Api\V2\{Problem, Responses};
use Closure;

class Boundary
{
    public function handle($request, Closure $next)
    {
        $responses = app(Responses::class);
        $responses->requestId($request);
        if (!app()->environment('local', 'testing') && !$request->isSecure()) {
            throw new Problem(400, 'https_required', 'Use HTTPS for this API.');
        }
        return $responses->headers($request, $next($request));
    }
}
