<?php

Route::namespace('WebApp')->name('users.')->group(function() {

	Route::get('profile', 'UsersController@profile')->name('profile');

	Route::prefix('users')->group(function() {

		Route::prefix('tutorial-requests')->name('tutorial-requests.')->group(function() {

			Route::post('{piece}', 'TutorialRequestsController@store')->name('store');

		});

		Route::prefix('performances')->name('performances.')->group(function() {

			Route::get('{piece}/upload-url', 'PerformancesController@uploadUrl')->withoutMiddleware(['log.webapp'])->name('upload-url');

			Route::post('{piece}', 'PerformancesController@store')->name('store');

			Route::delete('{performance}', 'PerformancesController@destroy')->name('destroy');

		});

		Route::prefix('favorites')->name('favorites.')->group(function() {

			Route::prefix('folders')->name('folders.')->group(function() {
	
				Route::get('{folder}', 'UsersController@folder')->name('show');

				Route::post('', 'FavoriteFoldersController@store')->name('store');

				Route::patch('{folder}', 'FavoriteFoldersController@update')->name('update');

				Route::delete('{folder}', 'FavoriteFoldersController@destroy')->name('delete');
	
			});

			Route::post('{piece}', 'FavoritesController@update')->name('update');

		});

	});

});