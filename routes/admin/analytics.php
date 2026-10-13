<?php

Route::prefix('analytics')->name('analytics.')->group(function() {

	Route::get('users', 'Admin\AnalyticsController@users')->name('users');

	Route::get('logs', 'Admin\AnalyticsController@logs')->name('logs');
	
	Route::get('memberships', 'Admin\AnalyticsController@memberships')->name('memberships');

	Route::get('subscriptions', 'Admin\AnalyticsController@subscriptions')->name('subscriptions');

	Route::get('pieces', 'Admin\AnalyticsController@pieces')->name('pieces');

	Route::get('favorites', 'Admin\AnalyticsController@favorites')->name('favorites');

	Route::get('composers', 'Admin\AnalyticsController@composers')->name('composers');

	Route::get('blog', 'Admin\AnalyticsController@blog')->name('blog');

	Route::get('quizzes', 'Admin\AnalyticsController@quizzes')->name('quizzes');

	Route::get('infographs', 'Admin\AnalyticsController@infographs')->name('infographs');
});