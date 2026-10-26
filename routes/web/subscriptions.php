<?php

Route::post('newsletter/subscribe', 'NewsletterSubscriptionsController@store')->middleware('throttle:2,1')->name('newsletter.subscribe');

Route::prefix('subscriptions')->name('subscriptions.')->group(function() {

	Route::get('modal', 'SubscriptionsController@modal')->name('modal');

	Route::get('unsubscribed', 'SubscriptionsController@unsubscribed')->name('unsubscribed');

	Route::get('{subscription}/unsubscribe/{list}', 'SubscriptionsController@unsubscribe')->name('unsubscribe');

});

Route::resource('subscriptions', 'SubscriptionsController');

Route::get('gift', 'UsersController@gift')->name('gift');
