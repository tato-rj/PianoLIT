<?php

namespace App\Http\Middleware;

use App\{Location, User};
use \Stevebauman\Location\Facades\Location as LocationApi;
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
        $ip = $this->getIp();

        $location = LocationApi::get($ip);

        $user = $this->getUser($request);

        if ($user && $location)
            $this->updateLocation($user, $location);

        return $next($request);
    }

    public function updateLocation(User $user, $location)
    {
        try {
            Location::createOrUpdate($user->id, [
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
