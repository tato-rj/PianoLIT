<?php

Route::prefix('crashcourses')->name('crashcourses.')->group(function() {

	Route::get('', 'Admin\CrashCoursesController@index')->name('index');

	Route::post('', 'Admin\CrashCoursesController@store')->name('store');

	Route::get('create', 'Admin\CrashCoursesController@create')->name('create');

	Route::prefix('topics')->name('topics.')->group(function() {

		Route::get('', 'Admin\CrashCourseTopicsController@index')->name('index');

		Route::post('', 'Admin\CrashCourseTopicsController@store')->name('store');

		Route::patch('{topic}', 'Admin\CrashCourseTopicsController@update')->name('update');

		Route::delete('{topic}', 'Admin\CrashCourseTopicsController@destroy')->name('destroy');

	});

	Route::prefix('susbcriptions')->name('subscriptions.')->group(function() {

		Route::get('', 'Admin\CrashCourseSubscriptionsController@index')->name('index');

		Route::post('{subscription}/resend', 'Admin\CrashCourseSubscriptionsController@resend')->name('resend');
		
		Route::post('{subscription}/next', 'Admin\CrashCourseSubscriptionsController@next')->name('next');

		Route::post('{subscription}/cancel', 'Admin\CrashCourseSubscriptionsController@cancel')->name('cancel');
	});

	Route::prefix('{crashcourse}/lessons')->name('lessons.')->group(function() {

		Route::get('', 'Admin\CrashCourseLessonsController@create')->name('create');

		Route::patch('reorder', 'Admin\CrashCourseLessonsController@reorder')->name('reorder');
		
		Route::get('{lesson}', 'Admin\CrashCourseLessonsController@edit')->name('edit');

		Route::get('{lesson}/preview', 'Admin\CrashCourseLessonsController@preview')->name('preview');

		Route::get('{lesson}/send-to', 'Admin\CrashCourseLessonsController@sendTo')->name('send-to');

		Route::post('', 'Admin\CrashCourseLessonsController@store')->name('store');

		Route::patch('{lesson}', 'Admin\CrashCourseLessonsController@update')->name('update');

		Route::delete('{lesson}', 'Admin\CrashCourseLessonsController@destroy')->name('destroy');

	});

	Route::get('{crashcourse}/edit', 'Admin\CrashCoursesController@edit')->name('edit');

	Route::get('{crashcourse}/feedback/preview', 'Admin\CrashCoursesController@feedbackPreview')->name('feedback.preview');

	Route::get('{crashcourse}/feedback/send-to', 'Admin\CrashCoursesController@feedbackSendTo')->name('feedback.send-to');
	
	Route::patch('{crashcourse}', 'Admin\CrashCoursesController@update')->name('update');

	Route::patch('{crashcourse}/status', 'Admin\CrashCoursesController@updateStatus')->name('update-status');

	Route::delete('{crashcourse}', 'Admin\CrashCoursesController@destroy')->name('destroy');
	
});