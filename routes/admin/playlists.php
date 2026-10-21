<?php

Route::prefix('playlists')->name('playlists.')->group(function() {
    Route::post('tags', 'Admin\PlaylistTagsController@store')->name('tags.store');
    Route::patch('tags/{playlistTag}', 'Admin\PlaylistTagsController@update')->name('tags.update');
    Route::delete('tags/{playlistTag}', 'Admin\PlaylistTagsController@destroy')->name('tags.destroy');

    Route::get('order', 'Admin\PlaylistsController@order')->name('order');
    Route::get('{playlist}/pieces', 'Admin\PlaylistsController@pieces')->name('pieces');

	Route::patch('reorder', 'Admin\PlaylistsController@reorder')->name('reorder');
	Route::patch('{playlist}/publication', 'Admin\PlaylistsController@togglePublication')->name('publication');

});
