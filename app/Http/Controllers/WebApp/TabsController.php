<?php

namespace App\Http\Controllers\WebApp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Api\Api;
use App\{Tag, Playlist};

class TabsController extends Controller
{
    public function discover(Api $api)
    {
    	$rows = $api->discover()->toArray();

    	return view('webapp.discover.index', compact('rows'));
    }

    public function explore()
    {
    	$categories = Tag::display()->groupBy('type');

    	return view('webapp.explore.index', compact('categories'));
    }

    public function search(Api $api, Request $request)
    {
        if ($request->wantsJson())
            return view('webapp.search.results', ['pieces' =>  $api->search($request)])->render();

        return view('webapp.search.index');
    }

    public function count(Api $api, Request $request)
    {
        if ($api->search($request))
            return view('webapp.explore.count', ['query' => $request->search, 'count' => $api->search($request)->getData()->count])->render();

        return abort(416, 'Empty search');
    }

    public function playlists()
    {
        $playlists = Playlist::byGroup(null)->has('pieces', '>', 5)->sorted()->get();
        $journey = Playlist::byGroup('journey')->has('pieces', '>', 5)->sorted()->get();

    	return view('webapp.playlists.index', compact(['playlists', 'journey']));
    }

    public function myPieces()
    {
    	return view('webapp.my-pieces.index');
    }

    public function settings()
    {
    	return view('webapp.settings.index');
    }
}
