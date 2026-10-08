<?php

Route::namespace('WebApp')->group(function() {

	Route::get('', 'TabsController@discover')->name('discover');

	Route::get('welcome', 'TabsController@discover')->name('welcome');

	Route::get('tour', 'TabsController@tour')->name('tour');

    Route::post('tour/result', 'MatchTourController@result')->name('tour.result');

	Route::get('explore', 'TabsController@explore')->name('explore');

    Route::get('latest', 'TabsController@latest')->name('latest');

	Route::get('highlights', 'TabsController@highlights')->name('highlights');

	Route::get('playlists', 'TabsController@playlists')->name('playlists');
	
	Route::get('my-pieces', 'TabsController@myPieces')->name('my-pieces');
	
	Route::get('settings', 'TabsController@settings')->name('settings');

});

Route::post('logout', 'Auth\WebApp\LoginController@logout')->middleware('auth:web')->name('logout');
