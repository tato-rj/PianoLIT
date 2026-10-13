<?php

Route::post('activity/events', 'ActivityController@store')->middleware('throttle:120,1')->name('activity.store');
