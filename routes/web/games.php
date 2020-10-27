<?php

Route::prefix('true-or-false')->name('true-or-false.')->group(function() {

	Route::get('', function() {
		$colors = ['#fbe3e3', '#fdecdb', '#fffbd4', '#d7f3e3', '#e0f4f2', '#deedf9', '#e0e3f5', '#efe7fb', '#feeaf1'];

		$statements = (new \App\Games\TrueOrFalse\TrueOrFalse)->statements(['easy', 'difficult']);

		return view('games.trueorfalse.index', compact(['statements', 'colors']));
	})->name('index');

	Route::get('feedback', function() {
		$feedback = (new \App\Games\TrueOrFalse\TrueOrFalse)->evaluate(request()->score, request()->count);

	    return view('games.components.feedback', compact('feedback'))->render();
	})->name('feedback');

});

Route::get('riddles', function() {
	$riddles = [
		'#fff' => ['Beethoven', 'Jane', 'Waltz', 'Trombone', 'Drum Harmonica'],
		'#d7f3e366' => ['Practice', 'Sonata', 'Nocturne', 'Find the right key'],
		'#ffffff' => ['Scales', 'Whole note and quarter note', 'Etude', 'Classical']
	];

	return view('games.riddles.index', compact('riddles'));
})->name('riddles');

Route::prefix('quizzes')->name('quizzes.')->group(function() {

	Route::get('', 'QuizzesController@index')->name('index');

	Route::get('/{quiz}', 'QuizzesController@show')->name('show');

	Route::get('/{quiz}/feedback', 'QuizzesController@feedback')->name('feedback');

});