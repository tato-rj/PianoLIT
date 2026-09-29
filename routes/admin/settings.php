<?php

Route::prefix('settings')->name('settings.')->group(function() {

	Route::get('', 'Admin\SettingsController@index')->name('index');

});