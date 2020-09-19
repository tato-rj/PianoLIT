<?php

Route::get('discover', 'Api\TabsController@discover')->name('discover');

Route::get('search', 'Api\TabsController@search')->middleware('search.driver')->name('search');

Route::get('tour', 'Api\TabsController@tour')->name('tour');

// OLD IOS INSTALLS WITH TAGS LISTED ONLY
Route::get('tags', 'Api\TabsController@tags')->name('tags');

Route::get('explore', 'Api\TabsController@explore')->name('explore');
