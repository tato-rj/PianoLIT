<?php

Route::get('feedback', 'Admin\FeedbackController@index')->name('feedback.index');
Route::delete('feedback/{feedback}', 'Admin\FeedbackController@destroy')->name('feedback.destroy');
