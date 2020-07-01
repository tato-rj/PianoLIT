<?php

Route::prefix('ebooks')->name('ebooks.')->group(function() {

	Route::get('', 'Admin\eBooksController@index')->name('index');

	Route::get('create', 'Admin\eBooksController@create')->name('create');

	Route::post('', 'Admin\eBooksController@store')->name('store');

	Route::prefix('topics')->name('topics.')->group(function() {
	
		Route::get('', 'Admin\eBooksController@topics')->name('index');

		Route::post('store', 'Admin\eBooksController@topicStore')->name('store');

		Route::patch('{topic}/update', 'Admin\eBooksController@topicUpdate')->name('update');
		
		Route::delete('{topic}/destroy', 'Admin\eBooksController@topicDestroy')->name('destroy');

	});

	Route::get('{ebook}', 'Admin\eBooksController@show')->name('show');

	Route::get('{ebook}/edit', 'Admin\eBooksController@edit')->name('edit');

	Route::patch('{ebook}/status', 'Admin\eBooksController@updateStatus')->name('update-status');

	Route::patch('{ebook}', 'Admin\eBooksController@update')->name('update');

	Route::delete('{ebook}', 'Admin\eBooksController@destroy')->name('destroy');

	Route::prefix('{ebook}/previews')->name('previews.')->group(function() {

		Route::post('', 'Admin\eBooksController@uploadPreview')->name('upload');

		Route::delete('', 'Admin\eBooksController@removePreview')->name('remove');

	});

});