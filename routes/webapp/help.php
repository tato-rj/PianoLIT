<?php

Route::namespace('WebApp')->group(function() {

	Route::get('terms', 'HelpController@terms')->name('terms');

	Route::get('privacy', 'HelpController@privacy')->name('privacy');

});