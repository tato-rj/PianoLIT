<?php

Route::get('', 'AdminsController@home')->name('home');

Route::resources([
    'pieces' => 'PiecesController',
    'composers' => 'ComposersController',
    'tags' => 'TagsController',
    'topics' => 'TopicsController',
    'editors' => 'EditorsController',
    'users' => 'UsersController',
    'timelines' => 'TimelinesController'
]);

Route::prefix('blog')->name('posts.')->group(function() {

	Route::get('', 'AdminsController@blog')->name('index');

	Route::get('create', 'PostsController@create')->name('create');

	Route::post('', 'PostsController@store')->name('store');

	Route::post('images/upload', 'PostsController@uploadImage')->name('upload-image');

	Route::post('images/remove', 'PostsController@removeImage')->name('remove-image');

	Route::get('{post}', 'PostsController@edit')->name('edit');

	Route::patch('{post}', 'PostsController@update')->name('update');

	Route::patch('{post}/status', 'PostsController@updateStatus')->name('update-status');

	Route::delete('{post}', 'PostsController@destroy')->name('destroy');

});

Route::prefix('api')->name('api.')->group(function() {

	Route::get('discover', 'ApiController@discover')->name('discover');

	Route::get('search', 'ApiController@search')->name('search');

	Route::get('tour', 'ApiController@tour')->name('tour');

});

Route::prefix('subscriptions')->name('subscriptions.')->group(function() {

	Route::get('', 'AdminsController@subscriptions')->name('index');

	Route::patch('{subscription}/status', 'SubscriptionsController@updateStatus')->name('update-status');

});

Route::prefix('statistics')->name('stats.')->group(function() {

	Route::get('users', 'StatsController@users')->name('users');

	Route::get('pieces', 'StatsController@pieces')->name('pieces');

	Route::get('blog', 'StatsController@blog')->name('blog');

});


Route::prefix('users')->name('users.')->group(function() {

	Route::patch('{user}', 'MembershipsController@updateTrial')->name('update-trial');

});

Route::prefix('memberships')->name('memberships.')->group(function() {

	Route::prefix('validate')->name('validate.')->group(function() {

		Route::get('', 'MembershipsController@validateAll')->name('all');

		Route::post('{user}', 'MembershipsController@validate')->name('user');
	
	});

	Route::delete('{user}', 'MembershipsController@destroy')->name('destroy');

});

Route::prefix('pieces')->name('pieces.')->group(function() {

	Route::post('/single-lookup', 'PiecesController@singleLookup')->name('single-lookup');
	
	Route::post('/multi-lookup', 'PiecesController@multiLookup')->name('multi-lookup');
	
	Route::post('/validate-name', 'PiecesController@validateName')->name('validate-name');

});
