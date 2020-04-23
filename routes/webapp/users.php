<?php

Route::namespace('WebApp')->prefix('users')->name('users.')->group(function() {

	Route::prefix('favorites')->name('favorites.')->group(function() {

		Route::post('{piece}/toggle', 'FavoritesController@toggle')->name('toggle');

	});

});