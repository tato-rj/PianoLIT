<?php

Route::prefix('playlists')->name('playlists.')->group(function() {

	Route::patch('reorder', 'Admin\PlaylistsController@reorder')->name('reorder');

});
		