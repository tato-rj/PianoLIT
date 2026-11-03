<?php

use App\Http\Controllers\Api\V2\{AuthController, DiscoverController, MatchTourController};
use App\Http\Middleware\Api\V2\Authenticate;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1,v2-login:')->name('auth.login');
// Capability URLs serve only prebuilt, bounded samples; never require/forward bearer.
Route::get('match-tour-media/{asset}/{kind}', [MatchTourController::class, 'media'])
    ->where(['asset' => '[a-f0-9]{64}', 'kind' => 'audio|excerpt|sample'])->name('match-tour-media');
Route::middleware(Authenticate::class)->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('discover', [DiscoverController::class, 'show'])->name('discover');
    Route::post('pieces/{piece}/view', [DiscoverController::class, 'view'])->where('piece', '[1-9][0-9]*')->name('pieces.view');
    Route::post('match-tours', [MatchTourController::class, 'start'])->middleware('throttle:10,1,v2-match-start:')->name('match-tours.start');
    Route::prefix('match-tours/{tour}')->where(['tour' => '[a-fA-F0-9-]{36}'])->group(function () {
        Route::get('', [MatchTourController::class, 'show'])->name('match-tours.show');
        Route::put('answers/{slot}', [MatchTourController::class, 'answer'])->where('slot', implode('|', \App\Services\Api\V2\MatchTours::SLOTS))->name('match-tours.answer');
        Route::get('result', [MatchTourController::class, 'result'])->name('match-tours.result');
    });
});
