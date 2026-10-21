<?php

Route::get('infographs/{infograph}/download', 'InfographicsController@download')->name('legacy.infographs.download')->middleware('auth');

Route::post('infographs/{infograph}/update-score', 'InfographicsController@updateScore')->name('legacy.infographs.update-score');

Route::get('infographics/{path?}', 'RedirectsController@learn')->where('path', '.*')->name('legacy.infographics');
