<?php

// Keep same-origin feedback working for quiz pages opened before the move.
Route::get('quizzes/{quiz}/feedback', 'QuizzesController@feedback')->name('legacy.quizzes.feedback');
Route::get('quizzes/{path?}', 'RedirectsController@learn')->where('path', '.*')->name('legacy.quizzes');
