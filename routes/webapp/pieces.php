<?php

Route::namespace('WebApp')->middleware('members-only')->prefix('pieces')->name('pieces.')->group(function() {

	Route::get('{piece}', 'PiecesController@show')->name('show');

	// Route::get('{piece}/next', 'PiecesController@next')->name('next');

	Route::get('{piece}/collection', 'PiecesController@collection')->name('collection');

	Route::get('{piece}/composer', 'PiecesController@composer')->name('composer');

	Route::get('{piece}/similar', 'PiecesController@similar')->name('similar');

	Route::get('{piece}/appleMusic', 'PiecesController@appleMusic')->name('apple-music');

	Route::get('{piece}/audio', 'PiecesController@audio')->name('audio');

	Route::get('{piece}/score', 'PiecesController@score')->name('score');
});