<?php

Route::prefix('search')->name('search.')->group(function() {

	Route::get('global', 'SearchController@global')->name('global');

});

Route::prefix('explore')->name('explore.')->group(function() {

	Route::get('', 'SearchController@index')->name('search');

});