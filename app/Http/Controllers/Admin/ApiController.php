<?php

namespace App\Http\Controllers\Admin;

use App\Api\Api;
use App\Tag;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ApiController extends Controller
{
    public function discover(Api $api, Request $request)
    {
        $key = \Redis::get('app.discover');

    	$collection = $api->discover()->toArray();

        return view('admin.pages.api.tabs.discover.index', compact(['collection', 'key']));
    }

    public function tour(Api $api, Request $request)
    {    	
        $levels = Tag::levels()->get();
        $lengths = Tag::lengths()->get();
        $moods = Tag::special()->get();

    	$pieces = $api->search($request);
                
        return view('admin.pages.api.tabs.tour.index', compact(['pieces', 'levels', 'lengths', 'moods']));
    }

    public function search(Api $api, Request $request)
    {
        $tags = Tag::display()->groupBy('type');

        $pieces = $api->search($request);

        if ($request->has('rendered'))
            return view('admin.pages.api.tabs.search.result-rows', compact('pieces'))->render();

        return view('admin.pages.api.tabs.search.index', compact(['pieces', 'tags']));
    }

    public function endpoints()
    {
        return view('admin.pages.api.endpoints.index');
    }
}
