<?php

Route::get('/', 'HomeController@index')->name('home');

Route::get('contact-us', 'HomeController@contact')->name('contact');

Route::get('terms-of-service', 'HomeController@terms')->name('terms');

Route::get('privacy-policy', 'HomeController@privacy')->name('privacy');
