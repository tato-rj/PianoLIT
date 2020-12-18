<?php

Route::prefix('reviews')->name('reviews.')->group(function() {

	Route::post('{model}/{id}', 'Admin\ReviewsController@store')->name('store');

	Route::delete('{review}', 'Admin\ReviewsController@destroy')->name('destroy');

});
