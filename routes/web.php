<?php

Route::get('teens', function() {
	$pieces = \App\Piece::all();
	$prodigies = collect();
	$pieces->each(function($piece) use ($prodigies) {
		if ($piece->composed_in && $piece->composer->date_of_birth) {
			$age = $piece->composed_in - $piece->composer->date_of_birth->year;
			if ($age < 20 && $age > 10)
				$prodigies->push($piece);
		}
	});

	return $prodigies;
});

Route::resources([
    'subscriptions' => 'SubscriptionsController'
]);

Route::prefix('subscriptions')->name('subscriptions.')->group(function() {

	Route::get('{subscription}/unsubscribe/{list}', 'SubscriptionsController@unsubscribe')->name('unsubscribe');

});

Route::get('contact-us', 'HomeController@contact')->name('contact');

Route::prefix('search')->name('search.')->group(function() {

	Route::get('', 'SearchController@index')->name('index');

	Route::get('{piece}/similar', 'SearchController@similar')->name('similar');

	Route::get('more', 'SearchController@moreRows')->name('more');

});


Route::prefix('campaigns')->group(function() {

	Route::get('birthdays', function() {
		return view('promotions.birthdays');
	});

});

Route::get('/', 'HomeController@index')->name('home');

Route::get('/load-pieces', 'HomeController@loadPieces')->name('load-pieces');

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
		'#fff' => ['Beethoven', 'Jane', 'Waltz', 'Trombone', 'Drum Harmonica'],
		'#d7f3e366' => ['Practice', 'Sonata', 'Nocturne', 'Find the right key'],
		'#ffffff' => ['Scales', 'Whole note and quarter note', 'Etude', 'Classical']
	];

	return view('riddles.index', compact('riddles'));
})->name('riddles');

Route::prefix('pieces')->name('pieces.')->group(function() {

	Route::get('{piece}', 'PiecesController@show')->name('show');

});

Route::prefix('resources')->name('resources.')->group(function() {

	Route::prefix('great-pianists')->name('pianists.')->group(function() {

		Route::get('', 'ResourcesController@pianists')->name('index');

		Route::get('{pianist}', 'ResourcesController@pianist')->name('show');

	});

	Route::get('timeline', 'ResourcesController@timeline')->name('timeline');

	Route::prefix('infographs')->name('infographs.')->group(function() {

		Route::get('', 'ResourcesController@infographs')->name('index');

		Route::get('load', 'InfographsController@load')->name('load');

		Route::get('search', 'InfographsController@search')->name('search');

		Route::get('{infograph}', 'InfographsController@show')->name('show');

	});

	Route::get('top-podcasts', 'ResourcesController@podcasts')->name('podcasts');
});

Route::prefix('tools')->name('tools.')->group(function() {

	Route::get('staff/{type?}', 'ToolsController@staff')->name('staff');

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

	Route::get('studio-policies', 'ToolsController@studioPolicy')->name('studio-policies');

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

Route::get('infographs/{infograph}/download', 'InfographsController@download')->name('infographs.download')->middleware('auth');

Route::post('infographs/{infograph}/update-score', 'InfographsController@updateScore')->name('infographs.update-score');

Route::get('terms-of-service', 'HomeController@terms')->name('terms');

Route::get('privacy-policy', 'HomeController@privacy')->name('privacy');
