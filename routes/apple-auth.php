<?php

use App\Http\Controllers\Auth\{AppleController, GoogleSecurityController};

Route::prefix('auth/apple')->name('apple.')->middleware(\App\Http\Middleware\GoogleAuthenticationBoundary::class)->group(function () {
    Route::get('', [AppleController::class, 'redirect'])->middleware('throttle:20,1,apple-redirect-')->name('redirect');
    Route::post('callback', [AppleController::class, 'callback'])->middleware('throttle:20,1,apple-callback-')
        ->withoutMiddleware([
            \App\Http\Middleware\EncryptCookies::class, \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class, \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class, \App\Http\Middleware\HandleCampaignAttribution::class,
        ])->name('callback');
    Route::get('complete/{attempt}', [AppleController::class, 'complete'])->middleware('throttle:20,1,apple-complete-')->name('complete');
    Route::get('link', [GoogleSecurityController::class, 'link'])->name('link');
    Route::post('link/password', [GoogleSecurityController::class, 'passwordProof'])->middleware('throttle:6,1,apple-link-password-')->name('link.password');
    Route::post('link/email', [GoogleSecurityController::class, 'sendEmail'])->middleware('throttle:3,1,apple-link-email-')->name('link.email');
    Route::get('verify/{challenge}', [GoogleSecurityController::class, 'email'])->middleware(['signed', 'throttle:10,1,apple-verify-'])->name('verify');
    Route::post('verify/{challenge}', [GoogleSecurityController::class, 'verifyEmail'])->middleware(['signed', 'throttle:10,1,apple-verify-complete-'])->name('verify.complete');
    Route::middleware('guest:web')->group(function () {
        Route::get('choose', [AppleController::class, 'choose'])->name('choose');
        Route::post('existing', [AppleController::class, 'existing'])->middleware('throttle:6,1,apple-existing-')->name('existing');
        Route::post('register', [AppleController::class, 'register'])->middleware('throttle:6,1,apple-register-')->name('register');
        Route::post('signup/email', [AppleController::class, 'requestEmail'])->middleware('throttle:3,1,apple-signup-email-')->name('signup.email');
        Route::get('signup/verify/{attempt}', [AppleController::class, 'signupEmail'])->middleware(['signed', 'throttle:10,1,apple-signup-verify-'])->name('signup.verify');
        Route::post('signup/verify/{attempt}', [AppleController::class, 'finishEmail'])->middleware(['signed', 'throttle:6,1,apple-signup-finish-'])->name('signup.finish');
    });
    Route::middleware('auth:web')->group(function () {
        Route::post('connect', [AppleController::class, 'connect'])->middleware('throttle:10,1,apple-connect-')->name('connect');
        Route::post('reauthenticate', [AppleController::class, 'reauthenticate'])->middleware('throttle:10,1,apple-reauthenticate-')->name('reauthenticate');
        Route::get('confirm', [GoogleSecurityController::class, 'confirm'])->name('confirm');
        Route::delete('disconnect', [GoogleSecurityController::class, 'disconnect'])->middleware('throttle:6,1,apple-disconnect-')->name('disconnect');
    });
});
