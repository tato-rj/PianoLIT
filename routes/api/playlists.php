<?php

Route::prefix('playlists')->name('playlists.')->group(function() {

	Route::get('{group?}', 'Api\TabsController@playlists')->name('index');

	Route::get('{playlist}/pieces', 'Api\PlaylistsController@show')->name('show');

});
