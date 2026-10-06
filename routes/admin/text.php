<?php

Route::post('text/improve', 'Admin\ImproveTextController')
    ->middleware('throttle:10,1')->name('text.improve');
