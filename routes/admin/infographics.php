<?php

Route::prefix('infographs')->name('infographs.')->group(function() {
	
	Route::get('', 'Admin\InfographicsController@index')->name('index');

	Route::post('', 'Admin\InfographicsController@store')->name('store');

	Route::prefix('topics')->name('topics.')->group(function() {
	
		Route::get('', 'Admin\InfographicsController@topics')->name('index');

		Route::post('store', 'Admin\InfographicsController@topicStore')->name('store');

		Route::patch('{topic}/update', 'Admin\InfographicsController@topicUpdate')->name('update');
		
		Route::delete('{topic}/destroy', 'Admin\InfographicsController@topicDestroy')->name('destroy');

	});

	Route::patch('{infograph}/status', 'Admin\InfographicsController@updateStatus')->name('update-status');

	Route::get('{infograph}', 'Admin\InfographicsController@edit')->name('edit');

	Route::patch('{infograph}', 'Admin\InfographicsController@update')->name('update');

	Route::delete('{infograph}', 'Admin\InfographicsController@destroy')->name('destroy');
	
});