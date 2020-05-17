<?php

Route::prefix('pieces')->name('pieces.')->group(function() {

	Route::post('single-lookup', 'Admin\PiecesController@singleLookup')->name('single-lookup');
	
	Route::post('multi-lookup', 'Admin\PiecesController@multiLookup')->name('multi-lookup');
	
	Route::post('validate-name', 'Admin\PiecesController@validateName')->name('validate-name');

	Route::get('description/auto-complete', 'Admin\PiecesController@descriptionAutoComplete')->name('description-auto-complete');

	Route::get('datatable', 'PiecesController@datatable')->name('datatable');

	Route::get('alerts/show', 'Admin\PiecesController@alerts')->name('alerts');

	Route::patch('{piece}/update-level', 'Admin\PiecesController@updateLevel')->name('update-level');

	Route::patch('{piece}/update-tag', 'Admin\PiecesController@updateTag')->name('update-tag');

	Route::patch('{piece}/highlight', 'Admin\PiecesController@highlight')->name('highlight');

	Route::get('{piece}/load-tags', 'Admin\PiecesController@loadTags')->name('load-tags');

	Route::get('{piece}/load-levels', 'Admin\PiecesController@loadLevels')->name('load-levels');

});