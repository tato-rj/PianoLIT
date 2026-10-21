<?php

Route::view('/', 'shop.coming-soon')->name('shop.home');

Route::middleware('location.update')->namespace('Shop')->group(function () {
    Route::prefix('ebooks')->name('ebooks.')->group(function () {
        Route::get('', 'eBooksController@index')->name('index');
        Route::get('{ebook}', 'eBooksController@show')->name('show');
        Route::get('topics/{topic}', 'eBooksController@topic')->name('topic');
        Route::get('{ebook}/checkout', 'eBooksController@checkout')->middleware('auth:web')->name('checkout');
    });

    Route::prefix('escores')->name('escores.')->group(function () {
        Route::get('', 'eScoresController@index')->name('index');
        Route::get('{escore}', 'eScoresController@show')->name('show');
        Route::get('topics/{topic}', 'eScoresController@topic')->name('topic');
        Route::get('{escore}/checkout', 'eScoresController@checkout')->middleware('auth:web')->name('checkout');
    });
});
