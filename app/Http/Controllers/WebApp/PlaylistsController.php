<?php

namespace App\Http\Controllers\WebApp;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Playlist;

class PlaylistsController extends Controller
{
    public function show(Playlist $playlist)
    {
        $pieces = \App\Services\WebApp\PieceCards::load($playlist->pieces()->has('tutorials')->get());

        return view('webapp.playlists.show', compact('playlist', 'pieces'));
    }
}
