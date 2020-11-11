<?php

Route::prefix('quiz')->name('quizzes.')->group(function() {

	Route::get('', 'Admin\AdminsController@quiz')->name('index');

	Route::get('create', 'Admin\QuizzesController@create')->name('create');

	Route::post('', 'Admin\QuizzesController@store')->name('store');

	Route::post('images/upload', 'Admin\QuizzesController@uploadImage')->name('upload-image');

	Route::post('images/remove', 'Admin\QuizzesController@removeImage')->name('remove-image');

	Route::prefix('media')->name('media.')->group(function() {
	
		Route::get('audio', 'Admin\QuizMediaController@audio')->name('audio');

		Route::get('images', 'Admin\QuizMediaController@images')->name('images');

		Route::post('{type}/store', 'Admin\QuizMediaController@store')->name('store');
		
		Route::delete('destroy', 'Admin\QuizMediaController@destroy')->name('destroy');

	});
	
	Route::prefix('topics')->name('topics.')->group(function() {
	
		Route::get('', 'Admin\AdminsController@quizTopics')->name('index');

		Route::post('store', 'Admin\QuizzesController@topicStore')->name('store');

		Route::patch('{topic}/update', 'Admin\QuizzesController@topicUpdate')->name('update');
		
		Route::delete('{topic}/destroy', 'Admin\QuizzesController@topicDestroy')->name('destroy');

	});

	Route::get('{quiz}', 'Admin\QuizzesController@edit')->name('edit');

	Route::patch('{quiz}', 'Admin\QuizzesController@update')->name('update');

	Route::patch('{quiz}/status', 'Admin\QuizzesController@updateStatus')->name('update-status');

	Route::delete('{quiz}', 'Admin\QuizzesController@destroy')->name('destroy');

});