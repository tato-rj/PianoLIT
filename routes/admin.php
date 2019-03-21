<?php

Route::get('', 'AdminsController@home')->name('home');

Route::resources([
    'pieces' => 'PiecesController',
    'composers' => 'ComposersController',
    'tags' => 'TagsController',
    'editors' => 'EditorsController',
    'users' => 'UsersController'
]);

Route::prefix('users')->name('users.')->group(function() {

	Route::patch('{user}', 'MembershipsController@updateTrial')->name('update-trial');

});

Route::prefix('memberships')->name('memberships.')->group(function() {

	Route::prefix('validate')->name('validate.')->group(function() {

		Route::get('', 'MembershipsController@validateAll')->name('all');

		Route::post('{user}', 'MembershipsController@validate')->name('user');
	
	});

});
