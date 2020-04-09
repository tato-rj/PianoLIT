<?php

Route::prefix('subscriptions')->name('subscriptions.')->group(function() {

	Route::get('{subscription}/unsubscribe/{list}', 'SubscriptionsController@unsubscribe')->name('unsubscribe');

});

Route::resource('subscriptions', 'SubscriptionsController');

Route::get('gift', 'UsersController@gift')->name('gift');
