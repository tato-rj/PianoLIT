<?php

Route::get('', 'AdminsController@home')->name('home');

Route::resources([
    'pieces' => 'PiecesController',
    'composers' => 'ComposersController',
    'tags' => 'TagsController',
    'editors' => 'EditorsController',
    'users' => 'UsersController'
]);

Route::prefix('statistics')->name('stats.')->group(function() {

	Route::get('users', 'StatsController@users')->name('users');

	Route::get('pieces', 'StatsController@pieces')->name('pieces');

});


Route::prefix('users')->name('users.')->group(function() {

	Route::patch('{user}', 'MembershipsController@updateTrial')->name('update-trial');

});

Route::prefix('memberships')->name('memberships.')->group(function() {

	Route::prefix('validate')->name('validate.')->group(function() {

		Route::get('', 'MembershipsController@validateAll')->name('all');

		Route::post('{user}', 'MembershipsController@validate')->name('user');
	
	});

});

Route::prefix('pieces')->name('pieces.')->group(function() {

		Route::post('/single-lookup', 'PiecesController@singleLookup')->name('single-lookup');
		
		Route::post('/multi-lookup', 'PiecesController@multiLookup')->name('multi-lookup');
		
		Route::post('/validate-name', 'PiecesController@validateName')->name('validate-name');

});