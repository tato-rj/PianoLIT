<?php

Route::namespace('WebApp')->prefix('playlists')->name('playlists.')->group(function() {
	
	Route::get('{playlist}', 'PlaylistsController@show')->name('show');

});