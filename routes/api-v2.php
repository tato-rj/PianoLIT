<?php

use App\Http\Controllers\Api\V2\{AuthController, DiscoverController};
use App\Http\Middleware\Api\V2\Authenticate;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1,v2-login:')->name('auth.login');
Route::middleware(Authenticate::class)->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('discover', [DiscoverController::class, 'show'])->name('discover');
    Route::post('pieces/{piece}/view', [DiscoverController::class, 'view'])->where('piece', '[1-9][0-9]*')->name('pieces.view');
});
