<?php

namespace App\Http\Middleware;

use App\Support\ProfileUrl;
use Closure;

class CanonicalProfile
{
    public function handle($request, Closure $next)
    {
        if ($request->getHost() !== parse_url(ProfileUrl::base(), PHP_URL_HOST) || $request->path() === 'my-profile') {
            // Never forward a cross-host POST or its credentials.
            abort_unless(in_array($request->method(), ['GET', 'HEAD'], true), 404);
            $query = $request->server('QUERY_STRING');
            return redirect(ProfileUrl::get().($query ? '?'.$query : ''), 301);
        }
        return $next($request)->header('Cache-Control', 'no-store, private');
    }
}
