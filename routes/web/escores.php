<?php

Route::namespace('Shop')->prefix('escores')->name('escores.')->group(function() {

	Route::get('', 'eScoresController@index')->name('index');

	Route::get('{escore}', 'eScoresController@show')->name('show');

	Route::get('topics/{topic}', 'eScoresController@topic')->name('topic');

	Route::middleware('auth:web')->group(function() {

		Route::get('{escore}/checkout', 'eScoresController@checkout')->name('checkout');
	
	});
	
});
