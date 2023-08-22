<?php

Route::prefix('api')->name('api.')->group(function() {

	Route::get('discover', 'Admin\ApiController@discover')->name('discover');

	Route::get('endpoints', 'Admin\ApiController@endpoints')->name('endpoints');

	// TEST UPLOAD
	
	Route::get('upload', function() {
		return view('upload');
	});

	Route::post('upload', 'Admin\ApiController@upload')->name('test-upload');

});