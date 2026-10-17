<?php

Route::prefix('analytics')->name('analytics.')->group(function() {

	Route::get('users', 'Admin\UserAnalyticsController@index')->name('users');
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('people', 'Admin\UserAnalyticsController@people')->name('people');
        Route::get('insights', 'Admin\UserAnalyticsController@insights')->name('insights');
        Route::get('visits/{visit}', 'Admin\UserAnalyticsController@journey')->whereUuid('visit')->name('journey');
        Route::delete('visits/{visit}', 'Admin\UserAnalyticsController@destroyVisit')->whereUuid('visit')->name('visits.destroy');
        Route::get('{user}/{tab?}', 'Admin\UserAnalyticsController@profile')->whereNumber('user')->where('tab', 'overview|visits|music|account')->name('profile');
    });

	Route::get('logs', 'Admin\AnalyticsController@logs')->name('logs');
	
	Route::get('memberships', 'Admin\AnalyticsController@memberships')->name('memberships');

	Route::get('pieces', 'Admin\AnalyticsController@pieces')->name('pieces');

	Route::get('favorites', 'Admin\AnalyticsController@favorites')->name('favorites');

	Route::get('composers', 'Admin\AnalyticsController@composers')->name('composers');

	Route::get('infographs', 'Admin\AnalyticsController@infographs')->name('infographs');
});
