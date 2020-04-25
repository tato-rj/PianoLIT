<?php

Route::namespace('WebApp')->prefix('search')->middleware('search.driver')->name('search.')->group(function() {
	
	Route::get('', 'SearchController@results')->name('results');

	Route::get('count', 'SearchController@count')->name('count');

});