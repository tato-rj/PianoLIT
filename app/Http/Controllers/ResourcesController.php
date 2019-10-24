<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\{Pianist, Piece, Timeline, Infograph};

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
        $infographs = Infograph::published()->inRandomOrder()->get();
        $types = Infograph::types();

		return view('resources.infographs.index', compact(['infographs', 'types']));
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

    public function timeline()
    {
        $timeline = Timeline::generate();

        return view('resources.timeline.index', compact('timeline'));        
    }
}
