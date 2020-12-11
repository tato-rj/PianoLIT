<?php

Route::prefix('api')->name('api.')->group(function() {

	Route::get('discover', 'Admin\ApiController@discover')->name('discover');

	Route::get('endpoints', 'Admin\ApiController@endpoints')->name('endpoints');

});