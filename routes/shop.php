<?php

Route::namespace('Shop')->group(function () {
    Route::get('checkout', 'CheckoutController@show')->block()->name('shop.checkout');
    Route::post('checkout/quote', 'CheckoutController@quote')->middleware('throttle:30,1')->block()->name('shop.checkout.quote');
    Route::post('checkout/prepare', 'CheckoutController@prepare')->middleware('throttle:30,1')->block()->name('shop.checkout.prepare');
    Route::post('checkout/complete', 'CheckoutController@complete')->middleware('throttle:30,1')->block()->name('shop.checkout.complete');
    Route::get('orders/{order}', 'CheckoutController@order')->whereUuid('order')->block()->name('shop.order');
    Route::get('track/{order}', '\App\Http\Controllers\OrderTrackingController@show')->whereUuid('order')->middleware('signed')->name('shop.order.track');
    Route::get('track/{order}/packages/{shipment}', '\App\Http\Controllers\OrderTrackingController@samplePackage')->whereUuid('order')->whereNumber('shipment')->middleware('signed')->name('shop.order.sample-package');
    Route::get('/', 'MerchandiseController@index')->name('shop.home');
    Route::get('products/{merchandise}', 'MerchandiseController@legacy')->where('merchandise', '[0-9]+')->name('shop.merchandise.legacy');
    Route::get('{category}', 'MerchandiseController@index')->where('category', 't-shirts|stickers|merchandise')->name('shop.category');
    Route::get('{category}/{slug}', 'MerchandiseController@show')->where('category', 't-shirts|stickers|merchandise')->name('shop.merchandise.show');
    Route::get('bag', 'MerchandiseController@bag')->name('shop.bag');
    Route::post('bag', 'MerchandiseController@add')->block()->name('shop.bag.add');
    Route::patch('bag/{merchandise}/{variant}', 'MerchandiseController@update')->where(['merchandise' => '[0-9]+', 'variant' => '[0-9]+'])->block()->name('shop.bag.update');
    Route::delete('bag/{merchandise}/{variant}', 'MerchandiseController@remove')->where(['merchandise' => '[0-9]+', 'variant' => '[0-9]+'])->block()->name('shop.bag.remove');
});

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
