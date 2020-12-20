<?php

Route::prefix('reviews')->name('reviews.')->group(function() {

	Route::get('', 'Admin\ReviewsController@index')->name('index');

	Route::post('{model}/{id}', 'Admin\ReviewsController@store')->name('store');

	Route::patch('{review}/status', 'Admin\ReviewsController@updateStatus')->name('update-status');

	Route::delete('{review}', 'Admin\ReviewsController@destroy')->name('destroy');

});
