<?php

Route::namespace('WebApp')->prefix('membership')->name('membership.')->group(function() {

	Route::get('pricing', 'MembershipsController@pricing')->middleware(['non-members-only'])->name('pricing');

	Route::get('edit', 'MembershipsController@edit')->name('edit');

	Route::get('validate-coupon', 'MembershipsController@validateCoupon')->name('validate-coupon');

	Route::get('{plan}/checkout', 'MembershipsController@checkout')->middleware(['non-members-only'])->name('checkout');

	Route::get('welcome', 'MembershipsController@success')->name('success');

	Route::post('{plan}/purchase', 'MembershipsController@purchase')->middleware(['non-members-only'])->name('purchase');

	Route::post('plan/update', 'MembershipsController@updatePlan')->name('update.plan');

	Route::post('card/update', 'MembershipsController@updateCard')->name('update.card');

	Route::post('status/update', 'MembershipsController@updateBillingStatus')->name('update.billing-status');

	Route::post('cancel', 'MembershipsController@cancel')->name('cancel');

	Route::post('resume', 'MembershipsController@resume')->name('resume');

});