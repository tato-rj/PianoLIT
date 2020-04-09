<?php

Route::prefix('pieces')->name('pieces.')->group(function() {

	Route::post('/views', 'Api\PiecesController@incrementViews')->name('increment-views'); // MOVE AWAY FROM HERE

	Route::get('/find', 'Api\PiecesController@show')->name('find');

	Route::post('/find', 'Api\PiecesController@show'); // REMOVE THIS

	Route::get('{piece}/timeline', 'Api\PiecesController@timeline')->name('timeline'); // DO WE NEED THIS?

	Route::get('{piece}/collection', 'Api\PiecesController@collection')->name('collection');

	Route::get('{piece}/similar', 'Api\PiecesController@similar')->name('similar');

});
