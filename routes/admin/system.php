<?php

Route::prefix('system')->name('system.')->group(function() {

	Route::get('php', 'Admin\SystemController@php')->name('php');

});