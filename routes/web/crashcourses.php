<?php

Route::get('crashcourses/{path?}', 'RedirectsController@learn')->where('path', '.*')->name('legacy.crashcourses');

// Already-open signup forms retain their original session and CSRF context.
Route::post('crashcourses/{crashcourse}/signup', 'CrashCoursesController@signup')->name('legacy.crashcourses.signup');
