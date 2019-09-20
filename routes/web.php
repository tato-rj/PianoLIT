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

Route::prefix('true-or-false')->name('true-or-false.')->group(function() {

	Route::get('', function() {
		$colors = ['#fbe3e3', '#fdecdb', '#fffbd4', '#d7f3e3', '#e0f4f2', '#deedf9', '#e0e3f5', '#efe7fb', '#feeaf1'];

		$statements = (new \App\Games\TrueOrFalse\TrueOrFalse)->statements(['easy', 'difficult']);

		return view('trueorfalse.index', compact(['statements', 'colors']));
	})->name('index');

	Route::get('feedback', function() {
		$feedback = (new \App\Games\TrueOrFalse\TrueOrFalse)->evaluate(request()->score, request()->count);

	    return view('components.games.feedback', compact('feedback'))->render();
	})->name('feedback');

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

Route::prefix('resources')->name('resources.')->group(function() {

	Route::prefix('great-pianists')->name('pianists.')->group(function() {

		Route::get('', 'ResourcesController@pianists')->name('index');

		Route::get('{pianist}', 'ResourcesController@pianist')->name('show');

	});

	Route::get('timeline', 'ResourcesController@timeline')->name('timeline');

	Route::get('staff/{type?}', 'ResourcesController@staff')->name('staff');

	Route::get('infographs/{name?}', 'ResourcesController@infographs')->name('infographs');

	Route::get('top-podcasts', 'ResourcesController@podcasts')->name('podcasts');

	Route::get('score/{piece}', 'ResourcesController@score')->name('score');
});

Route::prefix('tools')->name('tools.')->group(function() {

	Route::prefix('chord-finder')->name('chord-finder.')->group(function() {

		Route::get('', 'ToolsController@chordFinder')->name('index');

		Route::get('analyse', 'ToolsController@analyseChord')->name('analyse');

	});

	Route::get('circle-of-fifths', 'ToolsController@circleOfFifths')->name('circle-of-fifths');
	
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
