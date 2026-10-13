<?php

Route::prefix('api')->name('api.')->group(function() {

	Route::get('discover', 'Admin\ApiController@discover')->name('discover');

});
