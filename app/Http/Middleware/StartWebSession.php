<?php

namespace App\Http\Middleware;

use App\Auth\SharedWebSessions;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;

class StartWebSession extends StartSession
{
    public function handle($request, Closure $next)
    {
        $sharing = app(SharedWebSessions::class);
        if (!$sharing->active($request)) return parent::handle($request, $next);
        if ($request->route()) {
            $request->route()->block(120, 15);
        }
        try {
            return parent::handle($request, $next);
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException $exception) {
            return $sharing->busyResponse($request);
        }
    }

    protected function handleStatefulRequest(Request $request, $session, Closure $next)
    {
        if (!app(SharedWebSessions::class)->active($request)) return parent::handleStatefulRequest($request, $session, $next);
        // This runs INSIDE Laravel's session lock. A request queued before a
        // login/logout must not recreate its deleted session and overwrite the
        // browser's newer cookie. Ask stale requests to reload; never forward an
        // old session ID to the new authenticated one.
        $id = $session->getId();
        $sharing = app(SharedWebSessions::class);
        if ($sharing->retired($id)) {
            if ($request->isMethod('GET') && $request->is('login') && $request->query('restart_session') === '1') {
                // Explicit recovery if the browser never received the rotated
                // cookie (for example it closed while signing in). Ordinary
                // stale/background requests must never reset a newer cookie.
                $session->setId(null);
                $id = $session->getId();
            } else {
                $response = $request->expectsJson()
                    ? response()->json(['message' => 'Your session changed. Reload the page or sign in again.'], 419)
                    : response()->view('errors.shared-session-expired', [], 419);
                return $response->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
            }
        }
        try {
            return parent::handleStatefulRequest($request, $session, $next);
        } finally {
            if ($id !== $session->getId()) $sharing->retire($id);
        }
    }
}
