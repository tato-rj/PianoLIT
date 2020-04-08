<?php

Route::prefix('pieces')->name('pieces.')->group(function() {

	Route::post('/views', 'Api\PiecesController@incrementViews')->name('increment-views'); // MOVE AWAY FROM HERE

	Route::get('/find', 'Api\PiecesController@show')->name('find');

	Route::post('/find', 'Api\PiecesController@show'); // REMOVE THIS

	Route::get('{piece}/timeline', 'Api\PiecesController@timeline')->name('timeline'); // DO WE NEED THIS?

	Route::get('{piece}/collection', 'Api\PiecesController@collection')->name('collection');

	Route::get('{piece}/similar', 'Api\PiecesController@similar')->name('similar');

});

Route::prefix('playlists')->name('playlists.')->group(function() {

	Route::get('{group}', 'Api\PlaylistsController@index')->name('index');

	Route::get('{playlist}/pieces', 'Api\PlaylistsController@show')->name('show');

});

Route::prefix('blog')->name('blog.')->group(function() {

	Route::get('/search', 'PostsController@search')->name('search'); // MOVE AWAY FROM HERE

});

Route::prefix('subscriptions')->name('subscriptions.')->group(function() {

	Route::post('/unsubscribe', 'SubscriptionsController@unsubscribe')->name('unsubscribe'); // MOVE AWAY FROM HERE

});

Route::prefix('users')->name('users.')->group(function() {

	Route::prefix('favorites')->name('favorites.')->group(function() {

		Route::get('/show', 'Api\FavoritesController@show')->name('show');

		Route::post('/show', 'Api\FavoritesController@show'); // REMOVE THIS

		Route::post('/update', 'FavoritesController@update')->name('update');

	});

	Route::prefix('tutorial-requests')->name('tutorial-requests.')->group(function() {

		Route::get('', 'Api\TutorialRequestsController@show')->name('show'); // CHANGE TUTORIAL REQUESTS TO THIS

		Route::post('', 'TutorialRequestsController@store')->name('store');

		Route::delete('cancel', 'TutorialRequestsController@destroy')->name('destroy'); // DO WE NEED THIS?

	});

	Route::post('', 'Auth\RegisterController@register')->name('store');

	Route::post('/login', 'Auth\Api\LoginController@login')->name('login');

	Route::get('{user}', 'ApiController@user')->name('show'); // DO WE NEED THIS?

	// Route::post('/suggestions', 'ApiController@suggestions')->name('suggestions');

});

Route::prefix('memberships')->name('memberships.')->group(function() {

	Route::post('', 'MembershipsController@store')->name('store'); // DO WE NEED THIS?

	Route::post('history', 'MembershipsController@history')->name('history'); // DO WE NEED THIS?

	Route::post('status', 'MembershipsController@status')->name('status'); // DO WE NEED THIS?

});

Route::get('discover', 'Api\TabsController@discover')->name('discover');

Route::get('search', 'Api\TabsController@search')->middleware('search.driver')->name('search');

Route::get('tour', 'Api\TabsController@tour')->name('tour');

Route::get('tags', 'Api\TabsController@tags')->name('tags');

// Route::get('composers', 'ApiController@composers')->name('composers');

// Route::get('users', 'ApiController@users')->name('users');

Route::get('tutorial-requests', 'Api\TutorialRequestsController@show')->name('tutorial-requests'); // REMOVE THIS. CHANGE TUTORIAL REQUESTS TO FULL PATH (FIND ABOVE)