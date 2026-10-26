<?php

use Illuminate\Support\Facades\Route;

Route::post('auth/login', 'AuthController@login')->middleware(\App\Http\Middleware\ApiV2\Throttle::class.':login')->name('auth.login');

Route::middleware([\App\Http\Middleware\ApiV2\Throttle::class, \App\Http\Middleware\ApiV2\Authenticate::class])->group(function () {
    Route::get('auth/me', 'AuthController@me')->name('auth.me');
    Route::post('auth/logout', 'AuthController@logout')->name('auth.logout');
    Route::get('discover', 'DiscoverController')->name('discover');
    Route::post('pieces/{pieceId}/view', 'PieceViewsController@store')->where('pieceId', '[1-9][0-9]{0,19}')->name('pieces.view');
});
