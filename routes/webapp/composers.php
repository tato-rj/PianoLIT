<?php

Route::namespace('WebApp')->prefix('composers')->name('composers.')->group(function() {

	Route::get('', 'ComposersController@index')->name('index');
	Route::get('globe', 'ComposersController@globe')->name('globe');

	Route::get('{composer}', 'ComposersController@show')->name('show');

});
