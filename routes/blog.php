<?php

Route::name('posts.')->group(function () {
    Route::get('/', 'PostsController@index')->name('index');

    Route::post('subscribe', 'NewsletterSubscriptionsController@store')->middleware('throttle:2,1')->name('subscribe');

    Route::get('{post}', 'PostsController@show')->name('show');

    Route::get('{post}/gift', 'PostsController@gift')->name('gift');

    Route::get('{post}/app', 'PostsController@app')->name('app');
});
