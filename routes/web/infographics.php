<?php

Route::get('infographs/{infograph}/download', 'InfographicsController@download')->name('infographs.download')->middleware('auth');

Route::post('infographs/{infograph}/update-score', 'InfographicsController@updateScore')->name('infographs.update-score');