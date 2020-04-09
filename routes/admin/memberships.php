<?php

Route::prefix('memberships')->name('memberships.')->group(function() {

	Route::prefix('validate')->name('validate.')->group(function() {

		Route::get('', 'MembershipsController@validateAll')->name('all');

		Route::post('{user}', 'MembershipsController@validateUser')->name('user');
	
	});

	Route::delete('{user}', 'MembershipsController@destroy')->name('destroy');

});