<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RedisController extends Controller
{
    public function update($key)
    {
    	\Redis::set($key, $key . '-' . now()->timestamp);
    	
    	return back()->with('status', 'The discover page has been refreshed. It will now reload each day at ' . now()->format('h:i'));
    }
}
