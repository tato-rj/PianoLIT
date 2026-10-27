<?php

Route::get('invite-friends', 'UsersController@invite')->name('invite');

Route::patch('{user}', 'UsersController@update')->middleware('throttle:6,1,profile-legacy-update-')->name('update');

Route::delete('{user}', 'UsersController@destroy')->middleware('throttle:6,1,profile-legacy-delete-')->name('destroy');

Route::patch('subscriptions/update/{list}', 'UsersController@updateSubscription')->name('subscriptions.update-list');
