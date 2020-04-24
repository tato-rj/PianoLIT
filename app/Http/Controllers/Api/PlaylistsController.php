<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Playlist;

class PlaylistsController extends Controller
{
    public function __construct()
    {
        $this->middleware('log.app');
    }
    
    public function index($group = null)
    {
        return Playlist::byGroup($group)->with('pieces')->has('pieces', '>', 5)->sorted()->complete();
    }

    public function show(Request $request, Playlist $playlist)
    {
        $pieces = $playlist->pieces->where('videos_array', '!=', null);
        
        $pieces->each->isFavorited($request->user_id);

        return $pieces->values();
    }
}
