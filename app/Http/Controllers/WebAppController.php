<?php

namespace App\Http\Controllers;

use App\Api\Api;
use App\Tag;
use Illuminate\Http\Request;

class WebAppController extends Controller
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

    public function playlists()
    {
    	return view('webapp.playlists.index');
    }

    public function myPieces()
    {
    	return view('webapp.my-pieces.index');
    }

    public function settings()
    {
    	return view('webapp.settings.index');
    }

    public function logout(Request $request)
    {
        \Auth::guard('web')->logout();

        $request->session()->invalidate();

        return redirect(config('app.url'));
    }
}
