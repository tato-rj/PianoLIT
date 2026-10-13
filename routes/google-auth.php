<?php

use App\Http\Controllers\Auth\{GoogleController, GoogleSecurityController, GoogleHandoffController};

Route::prefix('auth/google')->name('google.')->middleware(\App\Http\Middleware\GoogleAuthenticationBoundary::class)->group(function () {
    Route::get('', [GoogleController::class, 'redirect'])->middleware('throttle:20,1,google-redirect-')->name('redirect');
    Route::get('callback', [GoogleController::class, 'callback'])->middleware('throttle:20,1,google-callback-')->name('callback');
    Route::get('link', [GoogleSecurityController::class, 'link'])->name('link');
    Route::post('link/password', [GoogleSecurityController::class, 'passwordProof'])->middleware('throttle:6,1,google-link-password-')->name('link.password');
    Route::post('link/email', [GoogleSecurityController::class, 'sendEmail'])->middleware('throttle:3,1,google-link-email-')->name('link.email');
    Route::get('verify/{challenge}', [GoogleSecurityController::class, 'email'])->middleware(['signed', 'throttle:10,1,google-verify-'])->name('verify');
    Route::post('verify/{challenge}', [GoogleSecurityController::class, 'verifyEmail'])->middleware(['signed', 'throttle:10,1,google-verify-complete-'])->name('verify.complete');
    Route::get('handoff/{handoff}', [GoogleHandoffController::class, 'begin'])->middleware('throttle:10,1,google-handoff-')->name('handoff');
    Route::post('handoff/{handoff}', [GoogleHandoffController::class, 'finish'])->middleware('throttle:10,1,google-handoff-finish-')->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class)->name('handoff.finish');
    Route::get('handoff/{handoff}/approve', [GoogleHandoffController::class, 'approve'])->middleware(['auth:web', 'throttle:10,1,google-handoff-approve-'])->name('handoff.approve');
    Route::middleware('auth:web')->group(function () {
        Route::get('accounts', [GoogleSecurityController::class, 'accounts'])->name('accounts');
        Route::post('connect', [GoogleController::class, 'connect'])->middleware('throttle:10,1,google-connect-')->name('connect');
        Route::post('reauthenticate', [GoogleController::class, 'reauthenticate'])->middleware('throttle:10,1,google-reauthenticate-')->name('reauthenticate');
        Route::get('confirm', [GoogleSecurityController::class, 'confirm'])->name('confirm');
        Route::get('password', [GoogleSecurityController::class, 'passwordForm'])->name('password');
        Route::post('password', [GoogleSecurityController::class, 'setPassword'])->middleware('throttle:6,1,google-password-set-')->name('password.set');
        Route::delete('disconnect', [GoogleSecurityController::class, 'disconnect'])->middleware('throttle:6,1,google-disconnect-')->name('disconnect');
    });
});
