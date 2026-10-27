<?php

namespace App\Http\Middleware;

use App\Support\ProfileUrl;
use Closure;

class CanonicalProfile
{
    public function handle($request, Closure $next)
    {
        if ($request->getHost() !== parse_url(ProfileUrl::base(), PHP_URL_HOST) || $request->is('my-profile', 'my-profile/purchases')) {
            // Never forward a cross-host POST or its credentials.
            abort_unless(in_array($request->method(), ['GET', 'HEAD'], true), 404);
            $query = $request->server('QUERY_STRING');
            $destination = $request->is('profile/downloads', 'my-profile/purchases') ? ProfileUrl::downloads() : ProfileUrl::get();
            return redirect($destination.($query ? '?'.$query : ''), 301);
        }
        return $next($request)->header('Cache-Control', 'no-store, private');
    }
}
