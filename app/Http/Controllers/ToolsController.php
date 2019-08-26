<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Resources\ChordFinder\ChordFinder;
use App\Resources\CircleOfFifths;

class ToolsController extends Controller
{
    public function chordFinder()
    {
		return view('tools.chords.index');
    }

    public function analyseChord()
    {
		$finder = new ChordFinder;
		$request = $finder->take(request())->validate()->analyse()->ranked()->get();
		$json = $finder->take(request())->debug();

		if (request()->has('dev'))
			return $request;
		
		return view('tools.chords.results.index', compact(['request', 'json']))->render();
    }

    public function circleOfFifths()
    {
		$keys = new CircleOfFifths;

		return view('tools.circle.index', compact('keys'));
    }
}
