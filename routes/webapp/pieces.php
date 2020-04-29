<?php

Route::namespace('WebApp')->prefix('pieces')->name('pieces.')->group(function() {

	Route::get('{piece}', 'PiecesController@show')->name('show');

	Route::get('{piece}/about', 'PiecesController@about')->name('about');

	Route::get('{piece}/composer', 'PiecesController@composer')->name('composer');

	Route::get('{piece}/similar', 'PiecesController@similar')->name('similar');

});