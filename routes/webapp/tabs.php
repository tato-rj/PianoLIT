<?php

Route::get('', 'WebAppController@discover')->name('discover');
Route::get('explore', 'WebAppController@discover')->name('explore');
Route::get('playlists', 'WebAppController@discover')->name('playlists');
Route::get('my-pieces', 'WebAppController@discover')->name('my-pieces');
Route::get('settings', 'WebAppController@discover')->name('settings');

Route::get('logout', function() {
	Auth::logout();

	return 'Logged out';
});