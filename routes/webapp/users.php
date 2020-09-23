<?php

Route::namespace('WebApp')->name('users.')->group(function() {

	Route::get('profile', 'UsersController@profile')->name('profile');

	Route::prefix('users')->group(function() {

		Route::prefix('tutorial-requests')->name('tutorial-requests.')->group(function() {

			Route::post('{piece}', 'TutorialRequestsController@store')->name('store');

		});

	});

});