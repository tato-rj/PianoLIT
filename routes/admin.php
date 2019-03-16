<?php

Route::get('', 'AdminController@home')->name('home');

Route::prefix('composers')->name('composers.')->group(function() {

	Route::post('', 'ComposerController@store')->name('store');

	Route::patch('{composer}', 'ComposerController@update')->name('update');

	Route::delete('{composer}', 'ComposerController@destroy')->name('destroy');

});