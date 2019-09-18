<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\{Pianist, Piece};

class ResourcesController extends Controller
{
    public function staff($type = null)
    {
    	$files = ['blank', 'piano', 'treble', 'bass'];
    	$size = request()->has('size') ? '-' . request('size') : null;

	   	if (in_array($type, $files))
	    	return response()->file('images/sheets/'.$type.$size.'.pdf');

		return view('resources.staff.index', compact('files'));
    }

    public function infographs($name = null)
    {
    	// $files = ['blank', 'piano', 'treble', 'bass'];

	   	// if (in_array($name, $files))
	    // 	return response()->file('images/sheets/'.$name.'.pdf');

		return view('resources.infographs.index');
    }

    public function podcasts()
    {
        return view('resources.podcasts.index');
    }

    public function pianists()
    {
        $pianists = Pianist::orderBy('name')->get();
        
        return view('resources.pianists.index', compact('pianists'));
    }

    public function pianist(Pianist $pianist)
    {
        return view('resources.pianists.show', compact('pianist'));        
    }

    public function score($piece)
    {
        $piece = Piece::find($piece);
        
        return view('pieces.score', compact('piece'));
    }
}
