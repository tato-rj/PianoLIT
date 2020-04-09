<?php

Route::prefix('subscriptions')->name('subscriptions.')->group(function() {

	Route::get('', 'Admin\AdminsController@subscriptions')->name('index');

	Route::get('export', 'SubscriptionsController@export')->name('export');

	Route::post('', 'Admin\SubscriptionsController@store')->name('store');

	Route::prefix('lists')->name('lists.')->group(function() {

		Route::get('', 'Admin\EmailListsController@index')->name('index');

		Route::get('preview/{list}', 'Admin\EmailListsController@preview')->name('preview');

		Route::get('send/{list}/to', 'Admin\EmailListsController@sendTo')->name('send-to');

		Route::get('send/{list}', 'Admin\EmailListsController@send')->name('send');

		Route::get('{list}/edit', 'Admin\EmailListsController@edit')->name('edit');

		Route::patch('{list}', 'Admin\EmailListsController@update')->name('update');

		Route::patch('{list}/status', 'Admin\EmailListsController@status')->name('status');

		Route::post('', 'Admin\EmailListsController@store')->name('store');

		Route::delete('{list}', 'Admin\EmailListsController@destroy')->name('destroy');

	});

	Route::prefix('reports')->name('reports.')->group(function() {

		Route::get('', 'Admin\EmailListsController@reports')->name('index');

		Route::get('{list}', 'Admin\EmailListsController@report')->name('show');

		Route::delete('{list}', 'Admin\EmailListsController@destroyReport')->name('destroy');

	});
});