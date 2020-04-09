<?php

Route::prefix('playlists')->name('playlists.')->group(function() {

	Route::get('{group}', 'Api\PlaylistsController@index')->name('index');

	Route::get('{playlist}/pieces', 'Api\PlaylistsController@show')->name('show');

});
