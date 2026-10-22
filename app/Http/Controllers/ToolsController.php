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
		return view('tools.chord-finder.index');
    }

    public function analyseChord()
    {
		$notation = '/^[A-Ga-g](?:\+{1,2}|-{1,2}|s{1,2})?$/';
		$data = request()->validate([
			'notes' => ['required', 'array', 'min:3', 'max:12'],
			'notes.*' => ['bail', 'required', 'string', 'regex:'.$notation],
			'root' => ['nullable', 'string', 'regex:'.$notation],
			'tool' => ['bail', 'nullable', 'string', 'in:button,keyboard'],
		]);
		abort_if(array_keys($data['notes']) !== range(0, count($data['notes']) - 1), 422, 'Please provide a list of notes.');
		$normalize = function ($note) { return str_replace('s', '+', strtolower($note)); };
		$notes = array_map($normalize, $data['notes']);
		abort_if(count(array_unique($notes)) < 3, 422, 'Please select at least 3 different notes.');
		abort_if(!empty($data['root']) && !in_array($normalize($data['root']), $notes, true), 422, 'The root must be one of your selected notes.');
		$finder = new ChordFinder;
		$request = $finder->take(request())->validate()->analyse()->ranked()->get();

		$json = $finder->take(request())->debug();

		if (request()->has('dev'))
			return $request;
		
		return view('tools.chord-finder.results.index', compact(['request', 'json']))->render();
    }

    public function circleOfFifths()
    {
		$keys = new CircleOfFifths;

		return view('tools.circle-of-fifths.index', compact('keys'));
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
