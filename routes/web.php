<?php

Route::resources([
    'subscriptions' => 'SubscriptionsController'
]);

Route::get('youtube', function() {
	return redirect(config('services.channels.youtube'));
})->name('youtube');

Route::get('/', function () {
	$videos = (new \App\Services\Youtube)->favorites(3);

	$tags = \App\Tag::inRandomOrder()->get();

    return view('welcome.index', compact(['tags', 'videos']));
})->name('home');

Route::get('/tools/circle-of-fifths', function() {
	$keys = new \App\Resources\CircleOfFifths;
});

Route::prefix('tools')->name('tools.')->group(function() {

	Route::prefix('chord-finder')->name('chord-finder.')->group(function() {

		Route::get('', function() {
			return view('tools.chords.index');
		});
		
		Route::get('/analyse', function() {
			$finder = new \App\Resources\ChordFinder\ChordFinder;
			$request = $finder->take(request()->notes)->analyse();

			return view('tools.chords.results.index', compact('request'))->render();
		})->name('analyse');

	});

});

Route::prefix('blog')->name('posts.')->group(function() {

	Route::get('', 'PostsController@index')->name('index');

	Route::get('/topics/{topic}', 'PostsController@topic')->name('topic');
	
	Route::get('/{post}', 'PostsController@show')->name('show');

});

Route::get('gift', 'UsersController@gift')->name('gift');
