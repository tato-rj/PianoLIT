<?php

Route::prefix('clips')->namespace('Admin')->name('clips.')->group(function() {

	Route::get('', 'ClipsController@index')->name('index');

	Route::post('', 'ClipsController@store')->name('store');

	Route::get('{clip}', 'ClipsController@edit')->name('edit');

	Route::patch('{clip}', 'ClipsController@update')->name('update');

	Route::delete('{clip}', 'ClipsController@destroy')->name('destroy');

});
