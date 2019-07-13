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

Route::prefix('tools')->name('tools.')->group(function() {

	Route::prefix('chord-finder')->name('chord-finder.')->group(function() {

		Route::get('', function() {
			$finder = new \App\Resources\ChordFinder\ChordFinder;
			try {
				$request = $finder->take(request()->test)->analyse();
			} catch (\Exception $e) {
				$request = [];
			}

			return view('tools.chords.index', compact('request'));
		})->name('index');
		
		Route::get('/analyse', function() {
			$finder = new \App\Resources\ChordFinder\ChordFinder;
			$request = $finder->take(request()->notes)->analyse();

			if (request()->has('dev'))
				return $request;
			
			return view('tools.chords.results.index', compact('request'))->render();
		})->name('analyse');

	});

	Route::get('circle-of-fifths', function() {
		$keys = new \App\Resources\CircleOfFifths;

		return view('tools.circle.index', compact('keys'));
	})->name('circle-of-fifths');

});

Route::prefix('blog')->name('posts.')->group(function() {

	Route::get('', 'PostsController@index')->name('index');

	Route::get('/topics/{topic}', 'PostsController@topic')->name('topic');
	
	Route::get('/{post}', 'PostsController@show')->name('show');

});

Route::get('gift', 'UsersController@gift')->name('gift');
