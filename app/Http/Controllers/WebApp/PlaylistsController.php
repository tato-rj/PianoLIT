<?php

namespace App\Http\Controllers\WebApp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Playlist;

class PlaylistsController extends Controller
{
    public function show(Playlist $playlist)
    {
    	return view('webapp.playlists.show.index', compact('playlist'));
    }
}
