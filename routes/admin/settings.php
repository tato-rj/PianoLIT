<?php

Route::prefix('emails')->name('emails.')->group(function () {
    Route::get('', 'Admin\EmailsController@index')->name('index');
    Route::post('send-preview', 'Admin\EmailsController@send')->middleware('throttle:10,1,admin-email-preview:')->name('send');
    Route::get('{email}/preview', 'Admin\EmailsController@preview')->name('preview');
    Route::patch('{email}/sending', 'Admin\EmailsController@sending')->name('sending');
    Route::get('{email}/content', 'Admin\EmailsController@content')->name('content');
});

Route::prefix('design-system')->name('design-system.')->group(function() {

	Route::get('', 'Admin\DesignSystemController@index')->name('index');

});
