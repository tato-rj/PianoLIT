<?php

Route::get('activity', 'Admin\ActivityController@index')->name('activity.index');
Route::get('activity/visits/{visit}', 'Admin\ActivityController@show')->name('activity.show');
Route::get('users/{user}/activity', 'Admin\ActivityController@user')->name('users.activity');
