<?php

Route::prefix('api')->name('api.')->group(function() {

	Route::get('discover', 'Admin\ApiController@discover')->name('discover');

	Route::get('search', 'Admin\ApiController@search')->middleware('search.driver')->name('search');

	Route::get('tour', 'Admin\ApiController@tour')->name('tour');

	Route::get('endpoints', 'Admin\ApiController@endpoints')->name('endpoints');

});