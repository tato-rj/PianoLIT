<?php

Route::namespace('WebApp')->name('users.')->group(function() {

	Route::get('profile', 'UsersController@profile')->name('profile');

	Route::prefix('users')->middleware('auth:web')->group(function() {

		Route::prefix('synthesia-requests')->name('synthesia-requests.')->group(function() {

			Route::post('{piece}', 'SynthesiaRequestsController@store')->name('store');

		});

        // Accept already-open browser forms on their original same-origin URL.
        Route::post('tutorial-requests/{piece}', 'SynthesiaRequestsController@store')->name('tutorial-requests.store');

		Route::prefix('favorites')->name('favorites.')->group(function() {

			Route::prefix('folders')->name('folders.')->group(function() {

                Route::patch('{folder}/reorder', 'FavoriteFoldersController@reorder')->name('reorder');
	
				Route::get('{folder}', 'UsersController@folder')->name('show');

				Route::match(['GET', 'POST'], '{folder}/pdf', 'FavoriteFoldersController@pdf')->name('pdf');
	
				Route::post('', 'FavoriteFoldersController@store')->name('store');

				Route::patch('{folder}', 'FavoriteFoldersController@update')->name('update');

				Route::delete('{folder}', 'FavoriteFoldersController@destroy')->name('delete');
	
			});

			Route::post('{piece}', 'FavoritesController@update')->name('update');

		});

	});

});
