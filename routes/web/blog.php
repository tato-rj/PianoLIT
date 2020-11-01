<?php

Route::prefix('blog')->name('posts.')->group(function() {

	Route::get('', 'PostsController@index')->name('index');
	
	Route::get('/{post}', 'PostsController@show')->name('show');

	Route::get('/{post}/app', 'PostsController@app')->name('app');

});
