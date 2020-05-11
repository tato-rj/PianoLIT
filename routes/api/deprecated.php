<?php

Route::get('tutorial-requests', 'Api\TutorialRequestsController@show')->name('tutorial-requests'); // REMOVE THIS. CHANGE TUTORIAL REQUESTS TO FULL PATH

Route::prefix('memberships')->name('memberships.')->group(function() {

	Route::post('', 'MembershipsController@store')->middleware(['membership.verify-status', 'membership.verify-apple'])->name('store');

	Route::post('history', 'MembershipsController@history')->name('history'); // DO WE NEED THIS?

	Route::post('status', 'MembershipsController@status')->name('status'); // DO WE NEED THIS?

});

Route::prefix('subscriptions')->name('subscriptions.')->group(function() {

	Route::post('/unsubscribe', 'SubscriptionsController@unsubscribe')->name('unsubscribe'); // MOVE AWAY FROM HERE

});
