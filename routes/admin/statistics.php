<?php

Route::prefix('statistics')->name('stats.')->group(function() {

	Route::get('users', 'Admin\StatsController@users')->name('users');

	Route::get('logs', 'Admin\StatsController@logs')->name('logs');
	
	Route::get('memberships', 'Admin\StatsController@memberships')->name('memberships');

	Route::get('subscriptions', 'Admin\StatsController@subscriptions')->name('subscriptions');

	Route::get('pieces', 'Admin\StatsController@pieces')->name('pieces');

	Route::get('composers', 'Admin\StatsController@composers')->name('composers');

	Route::get('blog', 'Admin\StatsController@blog')->name('blog');

	Route::get('quizzes', 'Admin\StatsController@quizzes')->name('quizzes');

	Route::get('infographs', 'Admin\StatsController@infographs')->name('infographs');
});