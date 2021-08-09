<?php

Route::namespace('WebApp')->middleware('members-only')->prefix('pieces')->name('pieces.')->group(function() {

	Route::get('{piece}', 'PiecesController@show')->name('show');

	Route::get('{piece}/collection', 'PiecesController@collection')->name('collection');

	Route::get('{piece}/composer', 'PiecesController@composer')->name('composer');

	Route::get('{piece}/timeline', 'PiecesController@timeline')->name('timeline');

	Route::get('{piece}/similar', 'PiecesController@similar')->name('similar');

	Route::get('{piece}/tutorial/{tutorial}', 'PiecesController@tutorial')->name('tutorial');
	
	Route::get('{piece}/appleMusic', 'PiecesController@appleMusic')->name('apple-music');

	Route::get('{piece}/audio', 'PiecesController@audio')->name('audio');

	Route::get('{piece}/score', 'PiecesController@score')->name('score');

	Route::get('{piece}/save-to', 'PiecesController@saveTo')->name('save-to');

	Route::post('{piece}/share', 'PiecesController@share')->name('share');
});