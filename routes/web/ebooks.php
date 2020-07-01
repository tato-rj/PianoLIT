<?php

Route::namespace('Shop')->prefix('ebooks')->name('ebooks.')->group(function() {

	Route::get('', 'eBooksController@index')->name('index');

	Route::get('{ebook}', 'eBooksController@show')->name('show');

	Route::get('topics/{topic}', 'eBooksController@topic')->name('topic');

	Route::middleware('auth:web')->group(function() {

		Route::get('{ebook}/checkout', 'eBooksController@checkout')->name('checkout');

		Route::post('{ebook}/purchase', 'eBooksController@purchase')->name('purchase');
	
	});
	
});
