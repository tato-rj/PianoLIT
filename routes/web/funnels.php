<?php

Route::name('funnels.')->group(function() {

	Route::prefix('find-your-match')->name('find-your-match.')->group(function() {

		Route::get('', 'FunnelsController@match')->name('index');

		Route::get('results', 'FunnelsController@matchResults')->name('results');

	});
	
});
