<?php

Route::post('feedback', 'WebApp\FeedbackController@store')->middleware('throttle:6,1,webapp-feedback')->name('feedback.store');
