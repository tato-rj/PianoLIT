<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Games\TrueOrFalse\TrueOrFalse;

class GamesController extends Controller
{
	public function trueOrFalse()
	{
		$colors = ['#fbe3e3', '#fdecdb', '#fffbd4', '#d7f3e3', '#e0f4f2', '#deedf9', '#e0e3f5', '#efe7fb', '#feeaf1'];

		$statements = (new TrueOrFalse)->statements(['easy', 'difficult']);

		return view('games.trueorfalse.index', compact(['statements', 'colors']));
	}

	public function trueOrFalseFeedback(Request $request)
	{
		$feedback = (new TrueOrFalse)->evaluate($request->score, $request->count);

	    return view('games.components.feedback', compact('feedback'))->render();
	}

	public function riddles()
	{
		$riddles = [
			'#fff' => ['Beethoven', 'Jane', 'Waltz', 'Trombone', 'Drum Harmonica'],
			'#d7f3e366' => ['Practice', 'Sonata', 'Nocturne', 'Find the right key'],
			'#ffffff' => ['Scales', 'Whole note and quarter note', 'Etude', 'Classical']
		];

		return view('games.riddles.index', compact('riddles'));
	}
}
