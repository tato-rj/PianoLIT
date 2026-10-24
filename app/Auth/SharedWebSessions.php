<?php

namespace App\Auth;

use Illuminate\Http\Request;

class SharedWebSessions
{
    const COOKIE = 'pianolit_web_session_v1';
    const REMEMBER_COOKIE = 'pianolit_web_remember_v1';
    const ATTRIBUTE = 'pianolit.shared_web_session';

    public function domain()
    {
        $domain = strtolower(ltrim((string) config('session.shared.domain'), '.'));
        if (!preg_match('/^(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}$/D', $domain)
            || $domain !== strtolower((string) config('app.short_url'))) {
            throw new \LogicException('Shared sessions require the exact PianoLIT parent domain.');
        }
        return $domain;
    }

    public function hosts()
    {
        $root = $this->domain();
        return array_map(function ($prefix) use ($root) { return $prefix.$root; },
            ['', 'www.', 'my.', 'learn.', 'shop.', 'blog.', 'games.', 'metronome.']);
    }

    public function supports(Request $request)
    {
        if (!config('session.shared.enabled')) return false;
        // Native JSON endpoints keep their original cookies, guards and payloads.
        if ($request->is('api', 'api/*') || ($request->isMethod('POST') && $request->is('register')
            && !in_array($request->input('origin'), ['web', 'webapp'], true))) return false;
        // Apple's cross-site POST is intentionally stateless; its GET completion
        // resumes the original browser session and validates the existing state.
        if ($request->isMethod('POST') && $request->is('auth/apple/callback')) return false;
        return in_array($request->getHost(), $this->hosts(), true);
    }

    public function active(Request $request)
    {
        return $request->attributes->get(self::ATTRIBUTE) === true;
    }

    public function sharesWith(Request $request, $host)
    {
        return $this->active($request) && in_array($host, $this->hosts(), true);
    }

    public function retired($id)
    {
        return \Cache::store(config('session.shared.lock_store'))->has('shared-web-retired:'.hash('sha256', $id));
    }

    public function retire($id)
    {
        \Cache::store(config('session.shared.lock_store'))->put('shared-web-retired:'.hash('sha256', $id), true,
            60 * max(1, (int) config('session.lifetime')));
    }

    public function busyResponse(Request $request)
    {
        $message = 'Another request is still finishing. Please try again.';
        $response = $request->expectsJson() ? response()->json(['message' => $message], 503) : response($message, 503);
        return $response->header('Retry-After', '1')->header('Cache-Control', 'no-store, private');
    }

    public function impersonate(Request $request, \App\User $user)
    {
        abort_unless(config('session.shared.enabled') && $request->getHost() === 'admin.'.$this->domain()
            && auth('admin')->check(), 403);
        // Open only the public session. Never copy the administrator session or
        // its guard/CSRF data into a cookie sent to the other subdomains.
        $store = (new \Illuminate\Session\SessionManager(app()))->driver();
        $store->setName(self::COOKIE);
        $store->setId($request->cookie(self::COOKIE));
        try {
            return \Cache::store(config('session.shared.lock_store'))->lock('session:'.$store->getId(), 120)->block(15,
                function () use ($store, $request, $user) {
                    $oldId = $store->getId();
                    $store->start();
                    $store->invalidate();
                    $store->regenerateToken();
                    $store->put('impersonator', true);
                    $guard = new SharedSessionGuard('web', app('auth')->createUserProvider(config('auth.guards.web.provider')), $store, $request);
                    $guard->setDispatcher(app('events'));
                    $guard->login($user, false);
                    $store->save();
                    $this->retire($oldId);
                    $secure = app()->environment('production') || (bool) config('session.secure');
                    $cookie = new \Symfony\Component\HttpFoundation\Cookie(self::COOKIE, $store->getId(),
                        config('session.expire_on_close') ? 0 : time() + 60 * config('session.lifetime'), '/', $this->domain(), $secure, true, false, 'lax');
                    $scheme = $secure ? 'https' : $request->getScheme();
                    return redirect($scheme.'://'.$this->domain().'/')->withCookie($cookie)
                        ->withCookie(cookie()->forget(self::REMEMBER_COOKIE, '/', $this->domain()));
                });
        } catch (\Illuminate\Contracts\Cache\LockTimeoutException $exception) {
            return $this->busyResponse($request);
        }
    }
}
