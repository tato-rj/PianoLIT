<?php

namespace App\Http\Middleware;

use App\Auth\Google\Configuration;
use Closure;

class GoogleAuthenticationBoundary
{
    public function handle($request, Closure $next)
    {
        abort_unless(in_array($request->getHost(), (new Configuration)->hosts(), true), 404);
        abort_if(app()->environment('production') && !$request->isSecure(), 400);
        try {
            if (!$request->routeIs('google.redirect', 'google.callback', 'google.accounts') && !(new Configuration)->ready()) {
                $response = redirect('/login')->with('error', 'Google sign-in is temporarily unavailable. Please use email and password.');
            } else {
                $response = $next($request);
            }
        } catch (\Illuminate\Routing\Exceptions\InvalidSignatureException $e) {
            $response = response()->view('auth.google.error', ['message' => 'This confirmation link is invalid or expired. Please start again.'], 403);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            if (!$request->routeIs('google.handoff*') || $e->getStatusCode() !== 403) throw $e;
            $response = response()->view('auth.google.error', ['message' => 'We could not finish this sign-in transfer. Please sign in again on the site you want to use.'], 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->expectsJson()) {
                $response = response()->json(['message' => 'Please check your account verification.', 'errors' => $e->errors()], 422);
            } else {
                $path = auth('web')->check() ? '/auth/google/accounts' : '/login';
                if (isset($e->errors()['password'])) {
                    $path = $request->routeIs('google.password.set') ? '/auth/google/password' : (auth('web')->check() ? '/auth/google/confirm' : '/auth/google/link');
                }
                $response = redirect($path)->withErrors($e->errors());
            }
        } catch (\Throwable $e) {
            // Exception context/HTTP response bodies can contain OAuth secrets.
            // Record only the failure class, never the exception or request data.
            \Log::warning('Google authentication operation failed', ['type' => get_class($e)]);
            $response = response()->view('auth.google.error', ['message' => 'We could not complete this account action. Please try again shortly.'], 503);
        }
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('Referrer-Policy', $request->routeIs('google.handoff.approve') ? 'origin' : 'no-referrer');
        $response->headers->set('X-Frame-Options', 'DENY');
        return $response;
    }
}
