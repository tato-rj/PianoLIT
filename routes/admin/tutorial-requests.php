<?php

Route::prefix('tutorial-requests')->name('tutorial-requests.')->group(function() {

	Route::get('', 'Admin\TutorialRequestsController@index')->name('index');

	Route::post('simulate', 'TutorialRequestsController@simulate')->name('simulate');
	
	Route::get('{tutorialRequest}', 'Admin\TutorialRequestsController@show')->name('show');

	Route::patch('{tutorialRequest}/publish', 'Admin\TutorialRequestsController@publish')->name('publish');

});