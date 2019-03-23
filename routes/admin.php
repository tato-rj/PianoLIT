<?php

Route::get('', 'AdminsController@home')->name('home');

Route::resources([
    'pieces' => 'PiecesController',
    'composers' => 'ComposersController',
    'tags' => 'TagsController',
    'editors' => 'EditorsController',
    'users' => 'UsersController',
    'posts' => 'PostsController'
]);

Route::prefix('api')->name('api.')->group(function() {

	Route::get('discover', 'ApiController@discover')->name('discover');

	Route::get('search', 'ApiController@search')->name('search');

	Route::get('tour', 'ApiController@tour')->name('tour');

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