<?php

Route::prefix('blog')->name('posts.')->group(function() {

	Route::get('', 'PostsController@index')->name('index');
	
	Route::get('/{post}', 'PostsController@show')->name('show');

});
