<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\DiscoverRefresh;

class RedisController extends Controller
{
    public function update(DiscoverRefresh $refresh)
    {
        $refresh->refresh();
    	
    	return back()->with('status', 'The discover page has been refreshed. It will now reload each day at ' . now()->format('h:i'));
    }
}
