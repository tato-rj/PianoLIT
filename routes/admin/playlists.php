<?php

Route::prefix('playlists')->name('playlists.')->group(function() {

	Route::patch('{group?}/reorder', 'Admin\PlaylistsController@reorder')->name('reorder');

});
		