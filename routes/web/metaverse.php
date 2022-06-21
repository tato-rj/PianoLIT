<?php

Route::prefix('metaverse')->name('metaverse.')->group(function() {

	Route::get('', 'MetaverseController@index')->name('index');

});