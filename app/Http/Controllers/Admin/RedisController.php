<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;

class RedisController extends Controller
{
	protected $keys = ['app.discover', 'app.explore'];

    public function update()
    {
    	foreach ($this->keys as $key) {
	    	Redis::set($key, $key . '-' . now()->timestamp);   		
    	}
    	
    	return back()->with('status', 'The discover page has been refreshed. It will now reload each day at ' . now()->format('h:i'));
    }
}
