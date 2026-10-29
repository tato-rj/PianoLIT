<?php

Route::get('shop-orders', 'Admin\ShopOrdersController@index')->name('shop-orders.index');
Route::post('shop-orders/simulations', 'Admin\LocalShopShipmentsController@create')->middleware('throttle:20,1')->name('shop-orders.simulation.create');
Route::post('shop-orders/{order}/simulate', 'Admin\LocalShopShipmentsController@simulate')->whereUuid('order')->middleware('throttle:30,1')->name('shop-orders.simulation.event');
Route::get('shop-orders/{order}/emails/{email}/preview/{format}', 'Admin\LocalShopShipmentsController@preview')->whereUuid('order')->whereNumber('email')->where('format', 'html|text')->name('shop-orders.simulation.email');
Route::get('shop-orders/{order}', 'Admin\ShopOrdersController@show')->whereUuid('order')->name('shop-orders.show');
Route::post('shop-orders/{order}/retry', 'Admin\ShopOrdersController@retry')->whereUuid('order')->name('shop-orders.retry');
Route::post('shop-orders/{order}/emails/{email}/retry', 'Admin\ShopOrdersController@retryEmail')->whereUuid('order')->whereNumber('email')->name('shop-orders.email.retry');
