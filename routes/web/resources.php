<?php

Route::prefix('resources')->name('resources.')->group(function() {

	Route::prefix('great-pianists')->name('pianists.')->group(function() {

		Route::get('', 'ResourcesController@pianists')->name('index');

		Route::get('{pianist}', 'ResourcesController@pianist')->name('show');

	});

	Route::get('timeline', 'ResourcesController@timeline')->name('timeline');

	Route::prefix('infographs')->name('infographs.')->group(function() {

		Route::get('', 'InfographicsController@index')->name('index');

		Route::get('load', 'InfographicsController@load')->name('load');

		Route::get('search', 'InfographicsController@search')->name('search');

		Route::get('{infograph}', 'InfographicsController@show')->name('show');

	});

	Route::get('top-podcasts', 'ResourcesController@podcasts')->name('podcasts');
});
