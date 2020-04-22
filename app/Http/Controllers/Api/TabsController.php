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
    	return $api->search($request);
    }

    public function search(Api $api, Request $request)
    {
        return $api->search($request);
    }

    public function tags()
    {
        return Tag::display();
    }
}
