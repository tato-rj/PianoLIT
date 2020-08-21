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
        $composers = $api->composersList();
    	$rows = $api->discover()->toArray();
        $post = $api->post();

    	return view('webapp.discover.index', compact(['rows', 'post', 'composers']));
    }

    public function explore()
    {
    	$categories = Tag::display()->groupBy('type');

    	return view('webapp.explore.index', compact('categories'));
    }

    public function playlists()
    {
        $playlists = Playlist::byGroup(null)->has('pieces', '>', 5)->sorted()->complete();
        $journey = Playlist::byGroup('journey')->has('pieces', '>', 5)->sorted()->get();

    	return view('webapp.playlists.index', compact(['playlists', 'journey']));
    }

    public function myPieces()
    {
    	return view('webapp.user.my-pieces.index');
    }

    public function settings()
    {      
    	return view('webapp.settings.index');
    }
}
