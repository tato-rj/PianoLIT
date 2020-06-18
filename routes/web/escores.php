<?php

Route::prefix('escores')->name('escores.')->group(function() {

	Route::get('', 'eScoresController@video')->name('index');

	Route::get('{escore}', 'eScoresController@ebooks')->name('show');
	
});
