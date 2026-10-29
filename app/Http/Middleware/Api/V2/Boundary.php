<?php

namespace App\Http\Middleware\Api\V2;

use App\Services\Api\V2\Response;
use Closure;
use Illuminate\Support\Str;
use Throwable;

class Boundary
{
    public function handle($request, Closure $next)
    {
        $request->attributes->set('api_v2_request_id', (string) Str::uuid());
        try {
            $response = $next($request);
        } catch (Throwable $error) {
            report($error);
            $response = Response::exception($error);
        }
        $response->headers->set('Cache-Control', 'no-store, private');
        return $response;
    }
}
