<?php

Auth::routes();

Route::prefix('admin')->name('admin.')->group(function() {
	
	Route::middleware('guest:admin')->prefix('login')->name('login.')->group(function() {
		
		Route::get('', 'Auth\Admin\LoginController@showLoginForm')->name('show');

		Route::post('', 'Auth\Admin\LoginController@login')->name('submit');
	
	});

});
