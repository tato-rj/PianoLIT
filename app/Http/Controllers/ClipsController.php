<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\{Clip, Piece};

class ClipsController extends Controller
{
    public function show(Request $request, Clip $clip)
    {
    	$url = $clip->url;

    	return view('media.clip', ['url' => $url, 'position' => $request->position]);
    }

    public function piece(Request $request, Piece $piece)
    {
    	if ($url = $piece->performance_url)
    		return view('media.clip', ['url' => $url, 'position' => $request->position]);
    	
    	return redirect(route('home'));    	
    }
}
