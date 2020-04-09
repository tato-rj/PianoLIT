<?php

Route::prefix('blog')->name('posts.')->group(function() {

	Route::get('', 'Admin\AdminsController@blog')->name('index');

	Route::get('create', 'Admin\BlogController@create')->name('create');

	Route::post('', 'Admin\BlogController@store')->name('store');

	Route::post('images/upload', 'Admin\BlogController@uploadImage')->name('upload-image');

	Route::post('images/remove', 'Admin\BlogController@removeImage')->name('remove-image');

	Route::prefix('audio')->name('audio.')->group(function() {
	
		Route::get('', 'Admin\BlogMediaController@audio')->name('index');

		Route::post('store', 'Admin\BlogMediaController@storeAudio')->name('store');
		
		Route::delete('destroy', 'Admin\BlogMediaController@destroyAudio')->name('destroy');

	});

	Route::prefix('gifts')->name('gifts.')->group(function() {
	
		Route::get('', 'Admin\BlogMediaController@gifts')->name('index');

		Route::post('store', 'Admin\BlogMediaController@storeGift')->name('store');
		
		Route::delete('destroy', 'Admin\BlogMediaController@destroyGift')->name('destroy');

	});

	Route::get('{post}', 'Admin\BlogController@edit')->name('edit');

	Route::patch('{post}', 'Admin\BlogController@update')->name('update');

	Route::patch('{post}/status', 'Admin\BlogController@updateStatus')->name('update-status');

	Route::delete('{post}', 'Admin\BlogController@destroy')->name('destroy');

});