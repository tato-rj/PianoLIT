<?php

Route::namespace('Shop')->middleware('auth:web')->prefix('shop')->name('shop.')->group(function() {

	Route::get('validate-coupon', 'ShopController@validateCoupon')->name('validate-coupon');

	Route::get('{purchase}/download', 'ShopController@download')->name('download');

	Route::post('{model}/{reference}/purchase', 'ShopController@purchase')->name('purchase');
	
	Route::get('{purchase}/thank-you', 'ShopController@success')->name('success');

});
