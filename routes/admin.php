<?php

Route::get('', 'AdminsController@home')->name('home');

Route::prefix('composers')->name('composers.')->group(function() {

	Route::post('', 'ComposersController@store')->name('store');

	Route::patch('{composer}', 'ComposersController@update')->name('update');

	Route::delete('{composer}', 'ComposersController@destroy')->name('destroy');

});

Route::prefix('pieces')->name('pieces.')->group(function() {

	Route::post('', 'PiecesController@store')->name('store');

	Route::patch('{piece}', 'PiecesController@update')->name('update');

	Route::delete('{piece}', 'PiecesController@destroy')->name('destroy');

});