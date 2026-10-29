<?php

Route::prefix('users')->name('users.')->group(function() {

	Route::patch('{user}/super-status', 'Admin\SuperUserAccessController@update')->name('super-status');

	Route::get('', 'Admin\UsersController@index')->name('index');

	Route::get('{user}', 'Admin\UsersController@show')->whereNumber('user')->name('show');

	Route::get('{user}/load-favorites', 'Admin\UsersController@loadFavorites')->name('load-favorites');

	Route::get('{user}/load-synthesia-requests', 'Admin\UsersController@loadSynthesiaRequests')->name('load-synthesia-requests');
	Route::get('{user}/load-requests', 'Admin\UsersController@loadSynthesiaRequests')->name('load-requests');

	Route::delete('destroy-many', 'Admin\UsersController@destroyMany')->name('destroy-many');

	Route::delete('{user}', 'Admin\UsersController@destroy')->name('destroy');
	
	Route::delete('{user}/purge', 'Admin\UsersController@purge')->name('purge');
});
