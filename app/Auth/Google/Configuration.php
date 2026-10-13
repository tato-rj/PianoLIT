<?php

namespace App\Auth\Google;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class Configuration
{
    public function hosts()
    {
        return [config('app.short_url'), 'my.'.config('app.short_url')];
    }

    public function base($host)
    {
        abort_unless(in_array($host, $this->hosts(), true), 404);
        return (app()->environment('production') ? 'https' : (parse_url(config('app.url'), PHP_URL_SCHEME) ?: 'http')).'://'.$host;
    }

    public function callback(Request $request)
    {
        $key = $request->getHost() === $this->hosts()[1] ? 'webapp_redirect_uri' : 'redirect_uri';
        $url = config('services.google_login.'.$key) ?: $this->base($request->getHost()).'/auth/google/callback';
        return $url === $this->base($request->getHost()).'/auth/google/callback' ? $url : null;
    }

    public function available(Request $request = null)
    {
        $request = $request ?: request();
        return in_array($request->getHost(), $this->hosts(), true)
            && config('services.google_login.client_id') && config('services.google_login.client_secret')
            && $this->callback($request)
            && $this->ready();
    }

    public function ready()
    {
        try {
            return Schema::hasTable('user_auth_providers') && Schema::hasTable('auth_link_challenges')
                && Schema::hasTable('auth_browser_handoffs') && Schema::hasTable('auth_provider_audits');
        } catch (\Throwable $e) { return false; }
    }

    public function destination($value, Request $request)
    {
        $fallback = $this->base($request->getHost()).'/';
        if (!is_string($value) || strlen($value) > 2000 || preg_match('/[\\\\\x00-\x20\x7f]/', $value)) return $fallback;
        if (substr($value, 0, 1) === '/' && substr($value, 0, 2) !== '//') $value = $this->base($request->getHost()).$value;
        $parts = parse_url($value);
        if (!$parts || !in_array($parts['host'] ?? '', $this->hosts(), true)
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])
            || ($parts['scheme'] ?? '') !== parse_url($this->base($parts['host']), PHP_URL_SCHEME)
            || preg_match('#^/(auth/|login(?:/|$)|register(?:/|$)|logout(?:/|$))#', $parts['path'] ?? '/')) return $fallback;
        return $value;
    }
}
