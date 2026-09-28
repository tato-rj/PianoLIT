<?php

Route::get('admin/{path?}', 'RedirectsController@admin')->where('path', '.*')->name('legacy.admin');

Route::get('youtube', 'RedirectsController@youtube')->name('youtube');

Route::get('download/ios', 'RedirectsController@ios')->name('download.ios');
