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

Route::get('true-or-not', function() {
	$questions = ['Lorem ipsum dolor sit amet' => true, 'Consectetur adipiscing elit' => true, 'Sed do eiusmod tempor incididunt ut' => false, 'Labore et dolore magna aliqua' => true, 'Ut enim ad minim veniam' => false, 'Quis nostrud exercitation ullamco' => false, 'Laboris nisi ut aliquip ex ea commodo consequat' => false, 'Duis aute irure dolor in reprehenderit in voluptate' => true, 'Velit esse cillum dolore' => false, 'Eu fugiat nulla pariatur' => true, 'Excepteur sint occaecat cupidatat non proident' => true, 'Sunt in culpa qui officia deserunt mollit anim id est laborum'];

	return view('trueornot.index', compact('questions'));
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
