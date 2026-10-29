<?php

Route::get('my-profile', function () { return redirect(\App\Support\ProfileUrl::get(), 301); })->name('users.profile.legacy');
Route::get('my-profile/purchases', function () { return redirect(\App\Support\ProfileUrl::downloads(), 301); })->name('users.purchases.legacy');

Route::prefix('profile')->name('users.')->middleware(['auth:web', 'log.web'])->group(function () {
    Route::get('', 'ProfileController@show')->name('profile');
    Route::get('downloads', 'UsersController@purchases')->name('purchases');
    Route::get('orders', 'OrderTrackingController@index')->name('orders');
    Route::patch('details', 'ProfileController@details')->name('profile.details');
    Route::patch('email', 'ProfileController@email')->middleware('throttle:6,1,profile-email-')->name('profile.email');
    Route::match(['GET', 'POST'], 'email/verify', 'ProfileController@verifyEmail')->middleware('signed')->name('profile.email.verify');
    Route::patch('password', 'ProfileController@password')->middleware('throttle:6,1,profile-password-')->name('profile.password');
    Route::patch('email-lists/{list}', 'ProfileController@subscription')->name('profile.subscription');
    Route::delete('email-lists', 'ProfileController@unsubscribeAll')->name('profile.unsubscribe-all');
    Route::delete('', 'ProfileController@destroy')->middleware('throttle:6,1,profile-delete-')->name('profile.destroy');
});
