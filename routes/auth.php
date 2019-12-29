<?php

Auth::routes(['verify' => true]);

Route::get('email/verified', function() {
	return view('auth.verified');
})->middleware('verified')->name('verification.verified');

Route::get('impersonate/{user}', 'Auth\LoginController@impersonate')->name('impersonate');

Route::prefix('admin')->name('admin.')->group(function() {
	
	Route::middleware('guest:admin')->prefix('login')->name('login.')->group(function() {
		
		Route::get('', 'Auth\Admin\LoginController@showLoginForm')->name('show');

		Route::post('', 'Auth\Admin\LoginController@login')->name('submit');
	
	});

});
