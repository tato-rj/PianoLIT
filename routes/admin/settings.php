<?php

Route::prefix('design-system')->name('design-system.')->group(function() {

	Route::get('', 'Admin\DesignSystemController@index')->name('index');

});
