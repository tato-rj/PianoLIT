<?php

namespace App\Analytics;

use App\Activity\ExcludedAccounts;
use Illuminate\Http\Request;

class WebsiteTracking
{
    public static function googleOnly(Request $request): bool
    {
        // The old container also has a Facebook pixel. Newly covered surfaces
        // receive only the Google measurement requested here.
        return ($request->getHost() === 'my.'.config('app.short_url') && !auth('web')->check())
            || $request->routeIs('metronome.home', 'games.*.play');
    }

    public static function enabled(Request $request): bool
    {
        $base = config('app.short_url');
        $hosts = array_map(function ($prefix) use ($base) { return $prefix.$base; },
            ['', 'www.', 'my.', 'learn.', 'blog.', 'shop.', 'games.', 'metronome.']);

        return app()->environment('production') && in_array($request->getHost(), $hosts, true)
            && !auth('admin')->check() && !ExcludedAccounts::request($request)
            && !$request->has('signature')
            && !$request->routeIs('admin.*', 'api.*', 'posts.app', 'password.reset', 'webapp.password.reset',
                'verification.verify', 'google.*', 'apple.*', 'subscriptions.unsubscribe');
    }
}
