<?php

namespace App\Http\Middleware;

use App\Services\CampaignAttribution;
use Closure;

class HandleCampaignAttribution
{
    public function handle($request, Closure $next)
    {
        $attribution = new CampaignAttribution;
        if (! $attribution->available($request)) return $next($request);
        $logout = $request->routeIs('webapp.logout', 'logout');
        if ($logout) $attribution->clearCookies($request);
        else $attribution->bind($request);
        $response = $next($request);
        try { $attribution->recordArrival($request, $response); }
        catch (\Throwable $exception) {
            // Reporting failures must not prevent visitors from opening music.
            report($exception);
        }
        // Registration/login regenerate the framework session; the encrypted,
        // host-only campaign cookie survives and is claimed by the web guard.
        if (! $logout) $attribution->bind($request);
        return $response;
    }
}
