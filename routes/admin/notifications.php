<?php

Route::prefix('notifications')->name('notifications.')->group(function() {

	Route::get('', 'Admin\AdminsController@notifications')->name('index');

	Route::get('read', 'NotificationsController@read')->name('read');

	Route::get('unread', 'NotificationsController@unread')->name('unread');

});