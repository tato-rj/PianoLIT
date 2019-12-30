<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CookiesController extends Controller
{
	public function test()
	{
        $visitors = \Redis::hgetall('visitor.*');

        foreach ($visitors as $index => $visitor) {
        	$visitors[$index]['info'] = collect(json_decode($visitor['info']));
        	$visitors[$index]['visits'] = collect(json_decode($visitor['visits']));
        }

        return $visitors;
	}

    public function store(Request $request)
    {
		// cookie()->queue(cookie()->forget('visitor_id'));
    	$visitorId = $request->cookie('visitor_id');

    	if (! $visitorId) {
		    $uniqueId = bcrypt(\Str::random() . time());

		    $data = [
		    	'id' => $uniqueId,
		    	'info' => collect(geoip(request()->ip())->toArray()),
		    	'visits' => json_encode([['date' => now(config('app.timezone')), 'url' => url()->previous()]])
		    ];

		    \Redis::hmset("visitor.$uniqueId", $data);

		    cookie()->queue(cookie()->forever('visitor_id', $uniqueId));
    	} else {
    		$visits = json_decode(\Redis::hgetall('visitor.' . request()->cookie('visitor_id'))['visits']);
    		array_unshift($visits, ['date' => now(config('app.timezone')), 'url' => url()->previous()]);

    		$data = [
		    	'info' => collect(geoip(request()->ip())->toArray()),
    			'visits' => json_encode($visits)
    		];

		    \Redis::hmset("visitor.$visitorId", $data);
    	}
    }
}
