<?php

namespace App\Http\Controllers\WebApp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Api\Api;
use App\{Tag, Playlist, Piece};

class TabsController extends Controller
{
    public function discover(Api $api)
    {
        $composers = $api->composersList();
    	$rows = $api->discover()->toArray();
        $post = $api->post();

    	return view('webapp.discover.index', compact(['rows', 'post', 'composers']));
    }

    public function explore(Api $api)
    {
    	$categories = Tag::display()->groupBy('type');
        $explore = $api->for('webapp')->explore();

    	return view('webapp.explore.index', compact(['categories', 'explore']));
    }

    public function highlights(Api $api, Request $request)
    {
        if ($request->wantsJson())
            return view('webapp.highlights.pieces', ['pieces' =>  Piece::freePicks()->filtered()->get()])->render();

        return view('webapp.highlights.index');
    }

    public function playlists()
    {
        $playlists = Playlist::byGroup(null)->has('pieces', '>', 5)->sorted()->complete();
        $journey = Playlist::byGroup('journey')->has('pieces', '>', 5)->sorted()->get();

    	return view('webapp.playlists.index', compact(['playlists', 'journey']));
    }

    public function tour()
    {
        return view('webapp.tour.index');
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
