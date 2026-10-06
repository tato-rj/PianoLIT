<?php

Route::post('composers/{composer}/regenerate-biography', 'Admin\ComposersController@regenerateBiography')
    ->middleware('throttle:10,1')->name('composers.regenerate-biography');

Route::patch('composers/{composer}/toggle-famous', 'Admin\ComposersController@toggleFamous')->name('composers.toggle-famous');
