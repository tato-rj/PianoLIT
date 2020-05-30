<?php

Route::namespace('WebApp')->prefix('blog')->name('blog.')->group(function() {

	Route::get('{post}', 'BlogController@show')->name('show');

});