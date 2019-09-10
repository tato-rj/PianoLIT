<?php

Route::resources([
    'subscriptions' => 'SubscriptionsController'
]);

Route::get('youtube', function() {
	return redirect(config('services.channels.youtube'));
})->name('youtube');

Route::get('/', function () {
	$tags = \App\Tag::inRandomOrder()->get();

    return view('welcome.index', compact(['tags']));
})->name('home');

Route::get('true-or-false', function() {
	$colors = ['#fbe3e3', '#fdecdb', '#fffbd4', '#d7f3e3', '#e0f4f2', '#deedf9', '#e0e3f5', '#efe7fb', '#feeaf1'];

	$statements = shuffle_assoc(['The piano has <u>88 keys</u>' => true, 'J.S.Bach was born in <u>Germany</u>' => true, 'The <strong>Damper Pedal</strong> makes the piano sound <u>softer</u>' => false, '<strong>G B D</strong> are the notes in a <u>G major chord</u>' => true, 'C.Debussy composed <u>9 Symphonies</u>' => false, 'W.A.Mozart died when he was <u>52 years old</u>' => false, 'L.V.Beethoven was a life long admirer of Haydn\'s music' => false, '<strong>F to Db</strong> is a <u>Minor 6th</u>' => true, '<strong>B to F</strong> is a <u>Perfect 5th</u>' => false, 'F.Chopin dedicated his Études Opus 10 to F.Liszt' => true, 'Debussy <u>did not</u> consider his music to be <i>Symbolistic</i>, rather than <i>Impressionistic</i>' => true, 'Bach was a prominant composer in the <u>classical period</u>' => false]);

	return view('trueornot.index', compact(['statements', 'colors']));
});

Route::get('riddles', function() {
	$riddles = [
		'#fff' => ['Beethoven', 'Jane', 'Waltz', 'Trombone'],
		'#d7f3e366' => ['Practice', 'Sonata', 'Nocturne', 'Find the right key'],
		// '#feeaf1' => ['Catching Zs', 'Night Owl', 'Beauty Sleep', 'Early Bird'],
		'#ffffff' => ['Scales', 'Whole note and quarter note', 'Etude', 'Classical']
	];

	return view('riddles.index', compact('riddles'));
})->name('riddles');

Route::prefix('tools')->name('tools.')->group(function() {

	Route::prefix('chord-finder')->name('chord-finder.')->group(function() {

		Route::get('', 'ToolsController@chordFinder')->name('index');

		Route::get('analyse', 'ToolsController@analyseChord')->name('analyse');

	});

	Route::get('circle-of-fifths', 'ToolsController@circleOfFifths')->name('circle-of-fifths');
	
	Route::get('staff/{type?}', 'ToolsController@staff')->name('staff');

	Route::prefix('scales')->name('scales.')->group(function() {

		Route::get('', 'ToolsController@scales')->name('index');

		Route::get('generate', 'ToolsController@generateScale')->name('generate');

	});

	Route::prefix('arpeggios')->name('arpeggios.')->group(function() {

		Route::get('', 'ToolsController@arpeggios')->name('index');

		Route::get('generate', 'ToolsController@generateArpeggio')->name('generate');

	});

});

Route::prefix('blog')->name('posts.')->group(function() {

	Route::get('', 'PostsController@index')->name('index');

	Route::get('/topics/{topic}', 'PostsController@topic')->name('topic');
	
	Route::get('/{post}', 'PostsController@show')->name('show');

});

Route::prefix('quizzes')->name('quizzes.')->group(function() {

	Route::get('', 'QuizzesController@index')->name('index');
	
	Route::get('/topics/{topic}', 'QuizzesController@topic')->name('topic');

	Route::get('/{quiz}', 'QuizzesController@show')->name('show');

	Route::get('/{quiz}/feedback', 'QuizzesController@feedback')->name('feedback');

});

Route::get('gift', 'UsersController@gift')->name('gift');
