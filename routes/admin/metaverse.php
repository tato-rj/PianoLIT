<?php

Route::prefix('metaverse')->name('metaverse.')->group(function() {

	Route::get('', 'Admin\MetaverseEventsController@index')->name('index');

	Route::post('', 'Admin\MetaverseEventsController@store')->name('store');

	Route::prefix('locations')->name('locations.')->group(function() {

		Route::get('', 'Admin\MetaverseLocationsController@index')->name('index');

		Route::post('', 'Admin\MetaverseLocationsController@store')->name('store');

		Route::prefix('{metaverseLocation}')->group(function() {

			Route::patch('', 'Admin\MetaverseLocationsController@update')->name('update');

			Route::delete('', 'Admin\MetaverseLocationsController@destroy')->name('destroy');

		});
	});

	Route::prefix('{metaverseEvent}')->name('event.')->group(function() {

		Route::patch('', 'Admin\MetaverseEventsController@update')->name('update');

		Route::delete('', 'Admin\MetaverseEventsController@destroy')->name('destroy');

	});
});