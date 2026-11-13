<?php

Route::prefix('timeline-events')->name('timeline-events.')->group(function() {
    Route::get('', 'Admin\TimelineEventsController@index')->name('index');
    Route::post('discover', 'Admin\TimelineEventsController@discover')->middleware('throttle:20,1')->name('discover');
    Route::post('events', 'Admin\TimelineEventsController@store')->name('store');
    Route::post('events/{event}/summary', 'Admin\TimelineEventsController@summary')->middleware('throttle:20,1')->name('summary');
    Route::patch('events/{event}', 'Admin\TimelineEventsController@update')->name('update');
    Route::delete('events/{event}', 'Admin\TimelineEventsController@destroy')->name('destroy');
});
