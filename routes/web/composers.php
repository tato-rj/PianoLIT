<?php

Route::prefix('composers')->name('composers.')->group(function() {

	Route::get('birthdays', 'ComposersController@birthdays')->name('birthdays');
	
});
