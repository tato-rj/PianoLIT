<?php

Route::prefix('notifications')->name('notifications.')->group(function() {

	Route::get('', 'Admin\AdminsController@notifications')->name('index');

	Route::get('panel', 'NotificationsController@panel')->name('panel');

	Route::match(['get', 'post'], 'read', 'NotificationsController@read')->name('read');

	Route::match(['get', 'post'], 'unread', 'NotificationsController@unread')->name('unread');

});
