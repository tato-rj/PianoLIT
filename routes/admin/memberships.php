<?php

Route::prefix('memberships')->name('memberships.')->group(function() {

	Route::prefix('validate')->name('validate.')->group(function() {

		Route::get('', 'MembershipsController@validateAll')->name('all');

		Route::post('{user}', 'MembershipsController@validateUser')->name('user');
	
	});

	Route::prefix('load')->name('load-')->group(function() {

		Route::get('trials', 'Admin\MembershipsController@loadTrials')->name('trials');

		Route::get('members', 'Admin\MembershipsController@loadMembers')->name('members');

		Route::get('expired', 'Admin\MembershipsController@loadExpired')->name('expired');

	});

	Route::delete('{user}', 'MembershipsController@destroy')->name('destroy');

});