<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CookiesController extends Controller
{
	public function test()
	{
		if (request('pass') != '11041984')
			abort(403, 'You don\'t have permission to see this');

		$visitors = [];
        $records = \Redis::scan('0', 'match', 'visitor.*', 'count', '100')[1];

        foreach ($records as $record) {
        	$data = \Redis::hgetall($record);

        	$visitor['id'] = $data['id'];
        	$visitor['info'] = collect(json_decode($data['info']));
        	$visitor['visits'] = collect(json_decode($data['visits']));

	        array_push($visitors, $visitor);
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
