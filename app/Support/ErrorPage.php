<?php

namespace App\Support;

use Illuminate\Http\Request;

class ErrorPage
{
    /** Recovery links never need the session, database, route table or browser history. */
    public static function context(Request $request, int $status): array
    {
        $admin = $request->getHost() === 'admin.'.config('app.short_url');
        $homeLabel = $admin ? 'Go to dashboard' : 'Go to home';
        $primary = ['url' => '/', 'label' => $homeLabel];
        $previous = self::previous($request);
        $current = $request->getRequestUri();
        if (substr($current, 0, 2) === '//' || preg_match('/[\x00-\x20\\\\]/', $current)) $current = '/';

        if ($status === 401) {
            $primary = ['url' => '/login', 'label' => 'Sign in'];
        } elseif ($status === 419) {
            $primary = $request->isMethodSafe()
                ? ['url' => $current, 'label' => 'Reload page']
                : ($previous ? ['url' => $previous, 'label' => 'Return to page'] : ['url' => '/login?restart_session=1', 'label' => 'Sign in again']);
        } elseif ($status === 413 || $status === 422 || $status === 405 || $status === 400) {
            if ($previous) $primary = ['url' => $previous, 'label' => 'Go back'];
        } elseif ($status >= 500 && $request->isMethodSafe()) {
            $primary = ['url' => $current, 'label' => 'Try again'];
        } elseif ($status >= 500 && $previous) {
            $primary = ['url' => $previous, 'label' => 'Go back'];
        }

        return compact('admin', 'homeLabel', 'primary');
    }

    private static function previous(Request $request): ?string
    {
        $referer = $request->headers->get('referer');
        if (!$referer || preg_match('/[\x00-\x20\\\\]/', $referer)) return null;
        $parts = parse_url($referer);
        if (!$parts || isset($parts['user']) || isset($parts['pass'])) return null;
        $scheme = strtolower($parts['scheme'] ?? '');
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);
        if ($scheme !== $request->getScheme() || strtolower($parts['host'] ?? '') !== $request->getHost() || $port !== $request->getPort()) return null;
        $path = $parts['path'] ?? '/';
        // Never let a path-only link become a protocol-relative URL, or loop back to this error.
        if (substr($path, 0, 2) === '//' || $path === $request->getPathInfo()) return null;
        return $path.(isset($parts['query']) ? '?'.$parts['query'] : '');
    }
}
