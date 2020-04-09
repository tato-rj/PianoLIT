<?php

Route::prefix('campaigns')->group(function() {

	Route::get('birthdays', function() {
		return view('promotions.birthdays');
	});

});
