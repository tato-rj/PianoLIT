<?php

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

});