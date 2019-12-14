<?php

Route::resources([
    'studio-policies' => 'StudioPoliciesController',
]);

Route::get('my-profile', 'UsersController@profile')->name('profile');

Route::get('invite-friends', 'UsersController@invite')->name('invite');

Route::prefix('users')->group(function() {

	Route::patch('{user}', 'UsersController@update')->name('update');

	Route::delete('{user}', 'UsersController@destroy')->name('destroy');

});