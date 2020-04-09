<?php

Route::prefix('users')->name('users.')->group(function() {

	Route::patch('{user}/super-status', 'MembershipsController@superStatus')->name('super-status');

	Route::get('', 'Admin\UsersController@index')->name('index');

	Route::get('logs', 'Admin\UsersController@logs')->name('logs');

	Route::get('{user}', 'Admin\UsersController@show')->name('show');

	Route::get('{user}/load-logs', 'Admin\UsersController@loadLogs')->name('load-logs');

	Route::get('{user}/load-favorites', 'Admin\UsersController@loadFavorites')->name('load-favorites');

	Route::get('{user}/load-requests', 'Admin\UsersController@loadRequests')->name('load-requests');

	Route::delete('destroy-many', 'Admin\UsersController@destroyMany')->name('destroy-many');

	Route::delete('{user}', 'Admin\UsersController@destroy')->name('destroy');
	
	Route::delete('{user}/purge', 'Admin\UsersController@purge')->name('purge');
});