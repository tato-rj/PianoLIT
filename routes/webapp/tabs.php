<?php

Route::get('', 'WebAppController@discover')->name('discover');
Route::get('explore', 'WebAppController@explore')->name('explore');
Route::get('playlists', 'WebAppController@playlists')->name('playlists');
Route::get('my-pieces', 'WebAppController@myPieces')->name('my-pieces');
Route::get('settings', 'WebAppController@settings')->name('settings');

Route::post('logout', 'WebAppController@logout')->name('logout');