<?php

Route::prefix('quiz')->name('quizzes.')->group(function() {

	Route::get('', 'Admin\AdminsController@quiz')->name('index');

	Route::get('create', 'QuizzesController@create')->name('create');

	Route::post('', 'QuizzesController@store')->name('store');

	Route::post('images/upload', 'QuizzesController@uploadImage')->name('upload-image');

	Route::post('images/remove', 'QuizzesController@removeImage')->name('remove-image');

	Route::prefix('media')->name('media.')->group(function() {
	
		Route::get('audio', 'QuizMediaController@audio')->name('audio');

		Route::get('images', 'QuizMediaController@images')->name('images');

		Route::post('{type}/store', 'QuizMediaController@store')->name('store');
		
		Route::delete('destroy', 'QuizMediaController@destroy')->name('destroy');

	});
	
	Route::delete('destroy', 'QuizMediaController@destroy')->name('destroy');

	Route::prefix('topics')->name('topics.')->group(function() {
	
		Route::get('', 'Admin\AdminsController@quizTopics')->name('index');

		Route::post('store', 'QuizzesController@topicStore')->name('store');

		Route::patch('{topic}/update', 'QuizzesController@topicUpdate')->name('update');
		
		Route::delete('{topic}/destroy', 'QuizzesController@topicDestroy')->name('destroy');

	});

	Route::get('{quiz}', 'QuizzesController@edit')->name('edit');

	Route::patch('{quiz}', 'QuizzesController@update')->name('update');

	Route::patch('{quiz}/status', 'QuizzesController@updateStatus')->name('update-status');

	Route::delete('{quiz}', 'QuizzesController@destroy')->name('destroy');

});