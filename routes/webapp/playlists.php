<?php

Route::namespace('WebApp')->middleware('member')->prefix('playlists')->name('playlists.')->group(function() {
	
	Route::get('{playlist}', 'PlaylistsController@show')->name('show');

});