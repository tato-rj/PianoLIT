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
    	if ($url = $piece->performance_url)
    		return view('media.clip', compact('url'));
    	
    	return redirect(route('home'));    	
    }
}
