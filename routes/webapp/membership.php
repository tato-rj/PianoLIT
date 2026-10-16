<?php

Route::namespace('WebApp')->prefix('membership')->name('membership.')->group(function() {

	Route::get('pricing', 'MembershipsController@pricing')->middleware(['non-members-only'])->name('pricing');

	Route::get('edit', 'MembershipsController@edit')->name('edit');

	Route::get('{plan}/checkout', 'MembershipsController@checkout')->middleware(['non-members-only'])->name('checkout');

	Route::get('welcome', 'MembershipsController@success')->name('success');

    Route::post('card/prepare', 'MembershipsController@prepareCard')->middleware('throttle:20,1')->name('prepare.card');
    Route::post('{plan}/prepare', 'MembershipsController@prepare')->middleware('throttle:20,1')->name('prepare');
    Route::post('{plan}/coupon', 'MembershipsController@coupon')->middleware('throttle:30,1')->name('coupon');
    Route::post('checkout/complete', 'MembershipsController@complete')->middleware('throttle:30,1')->name('complete');
    Route::get('invoices', 'MembershipsController@invoices')->middleware('throttle:30,1')->name('invoices');
	Route::post('{plan}/purchase', 'MembershipsController@purchase')->middleware('throttle:20,1')->name('purchase');

	Route::post('plan/update', 'MembershipsController@updatePlan')->name('update.plan');

	Route::post('card/update', 'MembershipsController@updateCard')->name('update.card');
    Route::post('card/remove', 'MembershipsController@removeCard')->middleware('throttle:20,1')->name('remove.card');

	Route::post('status/update', 'MembershipsController@updateBillingStatus')->name('update.billing-status');

	Route::post('cancel', 'MembershipsController@cancel')->name('cancel');

	Route::post('resume', 'MembershipsController@resume')->name('resume');

});
