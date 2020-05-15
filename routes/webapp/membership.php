<?php

Route::namespace('WebApp')->prefix('membership')->name('membership.')->group(function() {

	Route::get('pricing', 'MembershipsController@pricing')->middleware(['membership.verify-status', 'membership.verify-stripe'])->name('pricing');

	Route::get('edit', 'MembershipsController@edit')->name('edit');

	Route::get('{plan}/checkout', 'MembershipsController@checkout')->middleware(['membership.verify-status', 'membership.verify-stripe'])->name('checkout');

	Route::get('welcome', 'MembershipsController@success')->name('success');

	Route::post('{plan}/purchase', 'MembershipsController@purchase')->middleware(['membership.verify-status', 'membership.verify-stripe'])->name('purchase');

	Route::post('plan/update', 'MembershipsController@updatePlan')->name('update.plan');

	Route::post('card/update', 'MembershipsController@updateCard')->name('update.card');

	Route::post('status/update', 'MembershipsController@updateBillingStatus')->name('update.billing-status');

	Route::post('cancel', 'MembershipsController@cancel')->name('cancel');

	Route::post('resume', 'MembershipsController@resume')->name('resume');

});