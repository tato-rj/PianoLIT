<?php

Route::prefix('stage')->name('stage.')->group(function() {

	Route::get('', 'Admin\PerformancesController@index')->name('index');
	
	// Route::get('{tutorialRequest}', 'Admin\TutorialRequestsController@show')->name('show');

	// Route::patch('{tutorialRequest}/publish', 'Admin\TutorialRequestsController@publish')->name('publish');

	// Route::delete('{tutorialRequest}', 'Admin\TutorialRequestsController@destroy')->name('destroy');

});