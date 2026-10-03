<?php

Route::namespace('WebApp')->prefix('playlists')->name('playlists.')->group(function() {
	
	Route::get('{playlist}/pdf', 'PlaylistsController@pdf')->middleware('auth:web')->name('pdf');

	Route::get('{playlist}', 'PlaylistsController@show')->name('show');

});
