<?php

Route::prefix('tutorial-requests')->name('tutorial-requests.')->group(function() {

	Route::get('', 'TutorialRequestsController@index')->name('index');

	Route::post('simulate', 'TutorialRequestsController@simulate')->name('simulate');
	
	Route::get('{tutorialRequest}', 'TutorialRequestsController@show')->name('show');

	Route::patch('{tutorialRequest}/publish', 'TutorialRequestsController@publish')->name('publish');

});