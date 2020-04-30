<?php

Route::namespace('WebApp')->prefix('composers')->name('composers.')->group(function() {

	Route::get('', 'ComposersController@index')->name('index');

});