<?php

namespace App\Http\Controllers\Api;

use App\Api\Api;
use App\{Tag, User, Timeline, Piece, Playlist};
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TabsController extends Controller
{
    public function __construct()
    {
        $this->middleware('log.app');
    }

    public function discover(Api $api)
    {
    	return $api->discover()->toArray();
    }

    public function tour(Api $api, Request $request)
    {
        return $api->search($request)->get();
    }

    public function search(Api $api, Request $request)
    {
        return $api->search($request)->get();
    }

    public function tags()
    {
        return Tag::display();
    }

    public function explore(Api $api)
    {
        return $api->explore()->toArray();
    }

    public function querySuggestions(Api $api)
    {
        return $api->querySuggestions()->toArray();
    }
}
