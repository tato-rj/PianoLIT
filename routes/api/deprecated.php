<?php

Route::get('tutorial-requests', 'Api\Legacy\SynthesiaRequestsController@show')->name('tutorial-requests'); // Permanent legacy-app compatibility endpoint.

Route::prefix('memberships')->name('memberships.')->group(function() {

	Route::post('', 'MembershipsController@store')->middleware(['membership.single-source'])->name('store');

	Route::post('history', 'MembershipsController@history')->name('history'); // DO WE NEED THIS?

	Route::post('status', 'MembershipsController@status')->name('status');

});

Route::prefix('subscriptions')->name('subscriptions.')->group(function() {

	Route::post('/unsubscribe', 'SubscriptionsController@unsubscribe')->name('unsubscribe'); // MOVE AWAY FROM HERE

});
