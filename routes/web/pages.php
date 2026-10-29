<?php

Route::get('/', 'HomeController@index')->name('home');

Route::get('support', 'HomeController@contact')->name('contact');

Route::get('contact-us', 'RedirectsController@support')->name('legacy.contact');

Route::get('terms-of-service', 'HomeController@terms')->name('terms');

Route::get('privacy-policy', 'HomeController@privacy')->name('privacy');
