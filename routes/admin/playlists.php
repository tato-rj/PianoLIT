<?php

Route::prefix('playlists')->name('playlists.')->group(function() {

	Route::patch('reorder', 'Admin\PlaylistsController@reorder')->name('reorder');
	Route::patch('{playlist}/publication', 'Admin\PlaylistsController@togglePublication')->name('publication');

});
