<?php

Route::namespace('WebApp')->middleware('members-only')->prefix('playlists')->name('playlists.')->group(function() {
	
	Route::get('{playlist}', 'PlaylistsController@show')->name('show');

});