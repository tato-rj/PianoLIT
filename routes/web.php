<?php

Route::get('/update-files', function() {
	$pieces = \App\Piece::all();

	foreach ($pieces as $piece) {
		$piece->update([
			'audio_path' => str_replace('pianolit', 'app', $piece->audio_path),
			'audio_path_lh' => str_replace('pianolit', 'app', $piece->audio_path_lh),
			'audio_path_rh' => str_replace('pianolit', 'app', $piece->audio_path_rh),
			'score_path' => str_replace('pianolit', 'app', $piece->score_path)
		]);
	}

	return 'All good';
});

Route::resources([
    'subscriptions' => 'SubscriptionsController'
]);

Route::get('/', function () {
	$tags = \App\Tag::inRandomOrder()->get();

    return view('welcome.index', compact('tags'));
})->name('home');

Route::prefix('blog')->name('posts.')->group(function() {

	Route::get('', 'PostsController@index')->name('index');

	Route::get('/topics/{topic}', 'PostsController@topic')->name('topic');
	
	Route::get('/{post}', 'PostsController@show')->name('show');

});

Route::get('gift/{gift}', 'UsersController@gift')->name('gift');
