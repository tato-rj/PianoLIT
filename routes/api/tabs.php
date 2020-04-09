<?php

Route::get('discover', 'Api\TabsController@discover')->name('discover');

Route::get('search', 'Api\TabsController@search')->middleware('search.driver')->name('search');

Route::get('tour', 'Api\TabsController@tour')->name('tour');

Route::get('tags', 'Api\TabsController@tags')->name('tags');
