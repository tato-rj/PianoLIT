<?php

use App\Http\Controllers\Api\V2\{AuthController, DiscoverController, FavoriteFoldersController, MatchTourController, PiecesController};
use App\Http\Middleware\Api\V2\Authenticate;
use Illuminate\Support\Facades\Route;

Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1,v2-login:')->name('auth.login');
// Capability URLs serve only prebuilt, bounded samples; never require/forward bearer.
Route::get('match-tour-media/{asset}/{kind}', [MatchTourController::class, 'media'])
    ->where(['asset' => '[a-f0-9]{64}', 'kind' => 'audio|excerpt|sample'])->name('match-tour-media');
Route::middleware(Authenticate::class)->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::get('discover', [DiscoverController::class, 'show'])->name('discover');
    Route::get('pieces/{piece}', [PiecesController::class, 'show'])->where('piece', '[1-9][0-9]*')->name('pieces.show');
    Route::post('pieces/{piece}/view', [DiscoverController::class, 'view'])->where('piece', '[1-9][0-9]*')->name('pieces.view');
    Route::get('favorite-folders', [FavoriteFoldersController::class, 'index'])->name('favorite-folders.index');
    Route::middleware('throttle:30,1,v2-favorites-write:')->group(function () {
        Route::post('favorite-folders', [FavoriteFoldersController::class, 'store'])->name('favorite-folders.store');
        Route::put('favorite-folders/{folder}/pieces/{piece}', [FavoriteFoldersController::class, 'save'])
            ->where(['folder' => '[1-9][0-9]*', 'piece' => '[1-9][0-9]*'])->name('favorite-folders.save');
        Route::delete('favorite-folders/{folder}/pieces/{piece}', [FavoriteFoldersController::class, 'remove'])
            ->where(['folder' => '[1-9][0-9]*', 'piece' => '[1-9][0-9]*'])->name('favorite-folders.remove');
    });
    Route::post('match-tours', [MatchTourController::class, 'start'])->middleware('throttle:10,1,v2-match-start:')->name('match-tours.start');
    Route::prefix('match-tours/{tour}')->where(['tour' => '[a-fA-F0-9-]{36}'])->group(function () {
        Route::get('', [MatchTourController::class, 'show'])->name('match-tours.show');
        Route::put('answers/{slot}', [MatchTourController::class, 'answer'])->where('slot', implode('|', \App\Services\Api\V2\MatchTours::SLOTS))->name('match-tours.answer');
        Route::get('result', [MatchTourController::class, 'result'])->name('match-tours.result');
    });
});
