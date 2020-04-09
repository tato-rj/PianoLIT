<?php

Route::prefix('search')->name('search.')->group(function() {

	Route::get('', 'SearchController@index')->name('index');

	Route::get('{piece}/similar', 'SearchController@similar')->name('similar');

	Route::get('more', 'SearchController@moreRows')->name('more');

});