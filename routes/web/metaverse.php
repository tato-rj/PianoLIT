<?php

Route::prefix('metaverse')->name('metaverse.')->group(function() {

	Route::get('', 'MetaverseEventsController@index')->name('index');

});