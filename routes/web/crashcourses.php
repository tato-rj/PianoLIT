<?php

Route::prefix('crashcourses')->name('crashcourses.')->group(function() {

	Route::get('', 'CrashCoursesController@index')->name('index');

	Route::get('video', 'CrashCoursesController@video')->name('video');

	Route::get('cancel', 'CrashCoursesController@cancel')->name('cancel');

	Route::get('{crashcourse}', 'CrashCoursesController@show')->name('show');

	Route::post('{crashcourse}/signup', 'CrashCoursesController@signup')->name('signup');
	
});
