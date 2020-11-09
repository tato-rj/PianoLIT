<?php

Route::prefix('true-or-false')->name('true-or-false.')->group(function() {

	Route::get('', 'GamesController@trueOrFalse')->name('index');

	Route::get('feedback', 'GamesController@trueOrFalseFeedback')->name('feedback');

});

Route::get('riddles', 'GamesController@riddles')->name('riddles');

Route::prefix('quizzes')->name('quizzes.')->group(function() {

	Route::get('', 'QuizzesController@index')->name('index');

	Route::get('/{quiz}', 'QuizzesController@show')->name('show');

	Route::get('/{quiz}/feedback', 'QuizzesController@feedback')->name('feedback');

});