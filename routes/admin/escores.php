<?php

Route::prefix('escores')->name('escores.')->group(function() {

	Route::get('', 'Admin\eScoresController@index')->name('index');

	Route::get('create', 'Admin\eScoresController@create')->name('create');

	Route::post('', 'Admin\eScoresController@store')->name('store');

	Route::prefix('topics')->name('topics.')->group(function() {
	
		Route::get('', 'Admin\eScoresController@topics')->name('index');

		Route::post('store', 'Admin\eScoresController@topicStore')->name('store');

		Route::patch('{topic}/update', 'Admin\eScoresController@topicUpdate')->name('update');
		
		Route::delete('{topic}/destroy', 'Admin\eScoresController@topicDestroy')->name('destroy');

	});

	Route::get('{escore}', 'Admin\eScoresController@show')->name('show');

	Route::get('{escore}/edit', 'Admin\eScoresController@edit')->name('edit');

	Route::patch('{escore}/status', 'Admin\eScoresController@updateStatus')->name('update-status');

	Route::patch('{escore}', 'Admin\eScoresController@update')->name('update');

	Route::delete('{escore}', 'Admin\eScoresController@destroy')->name('destroy');

	Route::prefix('{escore}/previews')->name('previews.')->group(function() {

		Route::post('', 'Admin\eScoresController@uploadPreview')->name('upload');

		Route::delete('', 'Admin\eScoresController@removePreview')->name('remove');

	});

});