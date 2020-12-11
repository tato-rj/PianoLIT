<?php

namespace App\Http\Controllers\Admin;

use App\Api\Api;
use App\Tag;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redis;

class ApiController extends Controller
{
    public function discover(Api $api, Request $request)
    {
        $key = Redis::get('app.discover');

    	$collection = $api->discover()->toArray();

        return view('admin.pages.api.discover.index', compact(['collection', 'key']));
    }

    public function endpoints()
    {
        return view('admin.pages.api.endpoints.index');
    }
}
