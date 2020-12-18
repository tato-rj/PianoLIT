<?php

Route::middleware(['auth:web'])->prefix('reviews')->name('reviews.')->group(function() {

	Route::post('{model}/{id}', 'ReviewsController@store')->name('store');

	Route::delete('{review}', 'ReviewsController@destroy')->name('destroy');

});
