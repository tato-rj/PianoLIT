<?php

Route::prefix('metaverse')->name('metaverse.')->group(function() {

	Route::get('', 'Admin\MetaverseController@index')->name('index');

	Route::post('', 'Admin\MetaverseController@store')->name('store');

	Route::prefix('locations')->name('locations.')->group(function() {

		Route::get('', 'Admin\MetaverseLocationsController@index')->name('index');

		Route::post('', 'Admin\MetaverseLocationsController@store')->name('store');

		Route::prefix('{metaverseLocation}')->group(function() {

			Route::patch('', 'Admin\MetaverseLocationsController@update')->name('update');

			Route::delete('', 'Admin\MetaverseLocationsController@destroy')->name('destroy');

		});
	});

	Route::prefix('{metaverse}')->name('event.')->group(function() {

		Route::patch('', 'Admin\MetaverseController@update')->name('update');

		Route::delete('', 'Admin\MetaverseController@destroy')->name('destroy');

	});
});