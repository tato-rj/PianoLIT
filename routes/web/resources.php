<?php

Route::get('test-upload', function() {
	return view('test');
});

Route::post('test-upload', 'HomeController@filetest')->name('test-upload');

Route::prefix('resources')->name('resources.')->group(function() {

	Route::prefix('great-pianists')->name('pianists.')->group(function() {

		Route::get('', 'PianistsController@index')->name('index');

		Route::get('{pianist}', 'PianistsController@show')->name('show');

	});

	Route::get('timeline', 'TimelineController@index')->name('timeline');

	Route::prefix('infographs')->name('infographs.')->group(function() {

		Route::get('', 'InfographicsController@index')->name('index');

		Route::get('load', 'InfographicsController@load')->name('load');

		Route::get('search', 'InfographicsController@search')->name('search');

		Route::get('{infograph}', 'InfographicsController@show')->name('show');

		Route::get('topics/{topic}', 'InfographicsController@topic')->name('topic');

	});

});
