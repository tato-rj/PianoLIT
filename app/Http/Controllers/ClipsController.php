<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\{Clip, Piece};

class ClipsController extends Controller
{
    public function show(Clip $clip)
    {
    	$url = $clip->url;

    	return view('media.clip', compact('url'));
    }

    public function piece(Piece $piece)
    {
    	if ($piece->videos_count == 0 || $piece->videos_array[0]['title'] != 'Performance')
    		return redirect(route('home'));

    	$url = $piece->videos_array[0]['video_url'];

    	return view('media.clip', compact('url'));
    }
}
