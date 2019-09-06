<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Resources\ChordFinder\ChordFinder;
use App\Resources\Technique\{Scale, Arpeggio, Template};
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
    	$files = ['blank', 'piano', 'treble', 'bass'];
    	$size = request()->has('size') ? '-' . request('size') : null;

	   	if (in_array($type, $files))
	    	return response()->file('images/sheets/'.$type.$size.'.pdf');

		return view('tools.staff.index', compact('files'));
    }

    public function scales()
    {
        return view('tools.technique.scales');
    }

    public function generateScale(Request $request)
    {
        $scale = (new Scale($request->key))->type('diatonic')->generate();

        if (request()->has('dev'))
            return $scale;
        
        return view('tools.technique.results.scales', compact('scale'))->render();
    }

    public function arpeggios()
    {
        return view('tools.technique.arpeggios');
    }

    public function generateArpeggio(Request $request)
    {
        $arpeggio = (new Arpeggio($request->key))->type('triad')->generate();

        if (request()->has('dev'))
            return $arpeggio;
        
        return view('tools.technique.results.arpeggios', compact('arpeggio'))->render();
    }
}
