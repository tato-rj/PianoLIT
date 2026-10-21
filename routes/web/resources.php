<?php

Route::post('test-upload', 'HomeController@filetest')->name('test-upload');

Route::get('resources/great-pianists/{path?}', 'RedirectsController@learn')->where('path', '.*')->name('legacy.resources.pianists');
Route::get('great-pianists/{path?}', 'RedirectsController@learn')->where('path', '.*')->name('legacy.pianists');
Route::get('resources/infographs/{path?}', 'RedirectsController@learn')->where('path', '.*')->name('legacy.resources.infographs');
