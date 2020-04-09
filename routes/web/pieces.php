<?php

Route::get('/load-pieces', 'HomeController@loadPieces')->name('load-pieces');

Route::prefix('pieces')->name('pieces.')->group(function() {

	Route::get('{piece}', 'PiecesController@show')->name('show');

});
