<?php

Route::prefix('users')->name('users.')->group(function() {

	Route::get('ios-review/check', 'Api\UsersController@shouldReview')->name('should-review');
	Route::post('ios-review/check', 'Api\UsersController@saveReview')->name('save-review');

	Route::prefix('favorites')->name('favorites.')->group(function() {

		Route::get('show', 'Api\FavoritesController@show')->name('show');

		Route::post('show', 'Api\FavoritesController@show'); // REMOVE THIS

		Route::post('update', 'FavoritesController@update')->name('update');

		Route::prefix('folders')->name('folders.')->group(function() {

			Route::get('', 'Api\FavoriteFoldersController@index')->name('index');

			Route::get('find', 'Api\FavoriteFoldersController@show')->name('show');

			Route::post('', 'Api\FavoriteFoldersController@store')->name('store');

			Route::patch('', 'Api\FavoriteFoldersController@update')->name('update');

			Route::delete('', 'Api\FavoriteFoldersController@destroy')->name('delete');

		});
	});

	Route::prefix('tutorial-requests')->name('tutorial-requests.')->group(function() {

		Route::get('', 'Api\TutorialRequestsController@show')->name('show'); // CHANGE TUTORIAL REQUESTS TO THIS

		Route::post('', 'TutorialRequestsController@store')->name('store');

	});

	Route::post('', 'Auth\RegisterController@register')->name('store');

	Route::post('/login', 'Auth\Api\LoginController@login')->name('login');

	// Route::get('{user}', 'ApiController@user')->name('show'); // DO WE NEED THIS?

});