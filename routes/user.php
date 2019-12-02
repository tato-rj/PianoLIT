<?php

Route::prefix('profile')->name('profile.')->group(function() {

	Route::get('', 'UsersController@profile')->name('show');

});
