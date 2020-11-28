<?php

Route::name('funnels.')->group(function() {

	Route::get('find-your-match', 'FunnelsController@findYourMatch')->name('find-your-match');
	
});
