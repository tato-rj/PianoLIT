<?php

Auth::routes(['verify' => true]);

Route::get('email/verified', 'Auth\VerificationController@verified')->middleware('verified')->name('verification.verified');

Route::get('impersonate/{user}', 'Auth\LoginController@impersonate')->name('impersonate');
