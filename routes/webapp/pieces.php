<?php

Route::namespace('WebApp')->middleware('member')->prefix('pieces')->name('pieces.')->group(function() {

	Route::get('{piece}', 'PiecesController@show')->name('show');

	Route::get('{piece}/about', 'PiecesController@about')->name('about');

	Route::get('{piece}/composer', 'PiecesController@composer')->name('composer');

	Route::get('{piece}/similar', 'PiecesController@similar')->name('similar');

	Route::get('{piece}/audio', 'PiecesController@audio')->name('audio');

});