<?php

Route::get('clips/{clip}', 'ClipsController@show')->name('clips.show');

Route::get('clips/piece/{piece}', 'ClipsController@piece')->name('clips.piece');
