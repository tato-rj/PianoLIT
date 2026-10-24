<?php

namespace App\Http\Middleware;

use App\Auth\SharedWebSessions;
use Closure;

class ConfigureWebSession
{
    public function handle($request, Closure $next)
    {
        $sharing = app(SharedWebSessions::class);
        if (!$sharing->supports($request)) return $next($request);

        abort_if(app()->environment('production') && !$request->isSecure(), 400, 'HTTPS is required.');
        if (config('session.domain') || !in_array(config('session.driver'), ['file', 'redis', 'database', 'array'], true)
            || (config('session.driver') === 'array' && !app()->environment('testing'))) {
            throw new \LogicException('Shared web sessions need a server-side store and an unset SESSION_DOMAIN.');
        }
        $session = config('session');
        $driver = config('auth.guards.web.driver');
        // Other cookies (campaign attribution, analytics, admin and XSRF) stay
        // host-only. Only the session and web recaller cross subdomains.
        $cookies = app('cookie');
        $secure = app()->environment('production') || (bool) config('session.secure');
        $request->attributes->set(SharedWebSessions::ATTRIBUTE, true);
        config(['session.cookie' => SharedWebSessions::COOKIE, 'session.domain' => $sharing->domain(),
            'session.secure' => $secure, 'session.same_site' => 'lax', 'session.http_only' => true,
            'session.block' => true, 'session.block_store' => config('session.shared.lock_store'),
            'auth.guards.web.driver' => 'shared-web-session']);
        $cookies->setDefaultPathAndDomain('/', null, $secure, 'lax');
        try {
            return $next($request);
        } finally {
            config(['session' => $session, 'auth.guards.web.driver' => $driver]);
            $cookies->setDefaultPathAndDomain($session['path'], $session['domain'], $session['secure'], $session['same_site']);
        }
    }
}
