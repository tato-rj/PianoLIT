<?php

Route::get('coming-soon', function() {
	return view('webapp.countdown');
})->name('countdown');

Route::namespace('WebApp')->name('users.')->group(function() {

	Route::get('profile', 'UsersController@profile')->name('profile');

	Route::prefix('users')->group(function() {

		Route::prefix('favorites')->name('favorites.')->group(function() {

			Route::post('{piece}/toggle', 'FavoritesController@toggle')->name('toggle');

		});

		Route::prefix('tutorial-requests')->name('tutorial-requests.')->group(function() {

			Route::post('{piece}', 'TutorialRequestsController@store')->name('store');

		});

	});

});