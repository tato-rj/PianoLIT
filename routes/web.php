<?php

Route::resources([
    'subscriptions' => 'SubscriptionsController'
]);

Route::get('youtube', function() {
	return redirect(config('services.youtube.channel'));
})->name('youtube');

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
