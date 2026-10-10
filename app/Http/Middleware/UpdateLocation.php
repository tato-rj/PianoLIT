<?php

namespace App\Http\Middleware;

use App\{Location, User};
use \Stevebauman\Location\Facades\Location as LocationApi;
use Illuminate\Http\Request;
use Closure;

class UpdateLocation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = $this->getUser($request);

        if (! $user)
            return $next($request);

        if ($request->routeIs('webapp.*')) {
            $this->deferWebLocation($request, $user);
            return $next($request);
        }

        $ip = $this->getIp();

        $location = LocationApi::get($ip);

        if ($user && $location)
            $this->updateLocation($user, $location);

        return $next($request);
    }

    private function deferWebLocation(Request $request, User $user)
    {
        // Location is optional profile metadata. Retry failures at most hourly
        // per signed-in browser, and never hold up its HTML response.
        $key = 'webapp.location_attempt';
        $attempt = $request->session()->get($key, []);
        if (($attempt['user_id'] ?? null) === $user->id
            && ($attempt['retry_after'] ?? 0) > now()->timestamp) {
            return;
        }
        $request->session()->put($key, [
            'user_id' => $user->id,
            'retry_after' => now()->timestamp + config('webapp.location_retry_seconds', 3600),
        ]);
        if ($user->location()->exists()) return;

        $request->attributes->set('webapp.location', ['user_id' => $user->id, 'ip' => $this->getIp()]);
    }

    public function terminate(Request $request, $response)
    {
        $pending = $request->attributes->get('webapp.location');
        if (!$pending) return;
        $request->attributes->remove('webapp.location');
        try {
            // Laravel runs this after sending the response. Another request may
            // already have filled the location in or deleted the account.
            $user = User::find($pending['user_id']);
            if (!$user || $user->location()->exists()) return;
            $location = LocationApi::get($pending['ip']);
            if ($location) {
                $user->location()->firstOrCreate([], $this->locationData($location));
            }
        } catch (\Throwable $exception) {
            // Provider failures and concurrent inserts are optional metadata;
            // the session timestamp permits a later retry without a request loop.
        }
    }

    private function locationData($location)
    {
        return [
            'ip' => $location->ip,
            'countryName' => $location->countryName,
            'countryCode' => $location->countryCode,
            'regionCode' => $location->regionCode,
            'regionName' => $location->regionName,
            'cityName' => $location->cityName,
            'latitude' => $location->latitude,
            'longitude' => $location->longitude,
        ];
    }

    public function updateLocation(User $user, $location)
    {
        try {
            Location::createIfNotExists($user->id, [
                'user_id' => $user->id,
                'ip' => $location->ip,
                'countryName' => $location->countryName,
                'countryCode' => $location->countryCode,
                'regionCode' => $location->regionCode,
                'regionName' => $location->regionName,
                'cityName' => $location->cityName,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
            ]);
        } catch (\Exception $e) {
            // move on
        }
    }

    public function getUser($request)
    {
        if ($request->routeIs('webapp.*'))
            return auth('web')->user();

        if (auth()->check())
            return auth()->user();

        if ($request->has('user_id') && User::where('id', $request->user_id))
            return User::find($request->id);
        
        return null;
    }

    public function getIp()
    {
        if (testing())
            return '69.142.144.48';

        if (local())
            return long2ip(mt_rand());

        return request()->ip();
    }
}
