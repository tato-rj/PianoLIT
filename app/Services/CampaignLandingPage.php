<?php

namespace App\Services;

use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Http\Request;

class CampaignLandingPage
{
    public static function capture(Request $request)
    {
        $route = $request->route();
        if (! $route instanceof \Illuminate\Routing\Route) return '';
        $path = '/'.ltrim($route->uri(), '/');
        foreach ($route->parameterNames() as $name) {
            $value = $route->parameter($name);
            if ($value === null && strpos($path, '{'.$name.'?}') !== false) {
                $path = str_replace('/{'.$name.'?}', '', $path);
                continue;
            }
            if ($value instanceof UrlRoutable) $value = $value->getRouteKey();
            // Store public content identity, never account/reset/unsubscribe
            // identities or arbitrary path values. Queries/fragments are omitted.
            $public = in_array($name, ['piece', 'composer', 'playlist', 'post', 'quiz', 'infograph', 'course', 'lesson', 'clip', 'tag', 'category'], true);
            $replacement = $public && (is_string($value) || is_int($value))
                ? rawurlencode((string) $value) : '[redacted]';
            $path = str_replace(['{'.$name.'}', '{'.$name.'?}'], $replacement, $path);
        }
        $url = $request->getSchemeAndHttpHost().($path === '' ? '/' : $path);
        return self::valid($url) ? $url : '';
    }

    public static function valid($url)
    {
        if (! is_string($url) || strlen($url) > 1024 || preg_match('/[\\\\\x00-\x20\x7f]/', $url)) return false;
        $parts = parse_url($url);
        return $parts && in_array($parts['scheme'] ?? '', ['http', 'https'], true)
            && in_array($parts['host'] ?? '', [config('app.short_url'), 'www.'.config('app.short_url'), 'my.'.config('app.short_url'), 'blog.'.config('app.short_url'), 'shop.'.config('app.short_url'), 'learn.'.config('app.short_url'), 'games.'.config('app.short_url'), 'metronome.'.config('app.short_url')], true)
            && ! isset($parts['user']) && ! isset($parts['pass'])
            && ! isset($parts['query']) && ! isset($parts['fragment'])
            && strpos($parts['path'] ?? '', '/') === 0;
    }
}
