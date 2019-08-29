<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Resources\ChordFinder\ChordFinder;
use App\Resources\Technique\{Scale, Arpeggio};
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

    public function staff($type = null)
    {
    	$files = ['blank', 'piano'];
    	$size = request()->has('size') ? '-' . request('size') : null;

	   	if (in_array($type, $files))
	    	return response()->file('images/sheets/'.$type.$size.'.pdf');

		return view('tools.staff.index', compact('files'));
    }

    public function scales()
    {
        return view('tools.scales.index');
    }

    public function generateScales(Request $request)
    {
        $scale = new Scale($request->key);
        $arpeggio = new Arpeggio($request->key);

        $results = [
            $scale->types(['diatonic'])->generate(),
            $arpeggio->types(['triad'])->generate()
        ];

        dd($results);
    }
}
