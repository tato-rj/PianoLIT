<?php

Route::namespace('WebApp')->prefix('pieces')->name('pieces.')->group(function() {

	Route::get('{piece}', 'PiecesController@show')->name('show');

	Route::get('{piece}/collection', 'PiecesController@collection')->name('collection');

	Route::get('{piece}/composer', 'PiecesController@composer')->name('composer');

	Route::get('{piece}/timeline', 'PiecesController@timeline')->name('timeline');

	Route::get('{piece}/similar', 'PiecesController@similar')->name('similar');

	Route::get('{piece}/tutorial/{tutorial}', 'PiecesController@tutorial')->name('tutorial');
	
	Route::get('{piece}/appleMusic', 'PiecesController@appleMusic')->name('apple-music');

	Route::get('{piece}/audio', 'PiecesController@audio')->name('audio');

	Route::get('{piece}/score', 'PiecesController@score')->name('score');

	Route::get('{piece}/save-to', 'PiecesController@saveTo')->middleware('auth:web')->name('save-to');

	Route::post('{piece}/share', 'PiecesController@share')->middleware('auth:web')->name('share');
});
