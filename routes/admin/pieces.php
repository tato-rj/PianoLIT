<?php

Route::prefix('pieces')->name('pieces.')->group(function() {

    Route::get('{piece}/timeline', 'Admin\PieceTimelineController@edit')->name('timeline.edit');
    Route::post('{piece}/timeline/discover', 'Admin\PieceTimelineController@discover')->middleware('throttle:20,1')->name('timeline.discover');
    Route::post('{piece}/timeline/events', 'Admin\PieceTimelineController@store')->name('timeline.store');
    Route::patch('{piece}/timeline/events/{event}', 'Admin\PieceTimelineController@update')->name('timeline.update');
    Route::delete('{piece}/timeline/events/{event}', 'Admin\PieceTimelineController@destroy')->name('timeline.destroy');

    Route::get('{piece}/videos/{tutorial}/moments', 'Admin\VideoMomentsController@edit')->name('videos.moments.edit');
    Route::put('{piece}/videos/{tutorial}/moments', 'Admin\VideoMomentsController@update')->name('videos.moments.update');

	Route::post('single-lookup', 'Admin\PiecesController@singleLookup')->name('single-lookup');
	
	Route::post('multi-lookup', 'Admin\PiecesController@multiLookup')->name('multi-lookup');
	
	Route::post('validate-name', 'Admin\PiecesController@validateName')->name('validate-name');

	Route::get('description/auto-complete', 'Admin\PiecesController@descriptionAutoComplete')->name('description-auto-complete');

	Route::get('datatable', 'PiecesController@datatable')->name('datatable');

	Route::get('alerts/show', 'Admin\PiecesController@alerts')->name('alerts');

	Route::patch('{piece}/update-level', 'Admin\PiecesController@updateLevel')->name('update-level');

	Route::patch('{piece}/update-tag', 'Admin\PiecesController@updateTag')->name('update-tag');

	Route::patch('{piece}/highlight', 'Admin\PiecesController@highlight')->name('highlight');

	Route::patch('{piece}/hijack', 'Admin\PiecesController@hijack')->name('hijack');

	Route::get('{piece}/load-tags', 'Admin\PiecesController@loadTags')->name('load-tags');

	Route::get('{piece}/load-levels', 'Admin\PiecesController@loadLevels')->name('load-levels');

	Route::get('{piece}/test-escore', 'Admin\PiecesController@testEscore')->name('test-escore');

	Route::delete('{piece}/file', 'Admin\PiecesController@destroyFile')->name('destroy-file');

});