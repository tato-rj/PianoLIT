<?php

use App\Mail\Welcome;
use App\Mail\Timeline\OnThisDay;
use App\{Composer, Subscription};

Route::get('welcome', function() {
	return new Welcome;
})->name('welcome');

Route::get('birthday', function () {
	$composer = request()->has('composer_id') ? Composer::find(request('composer_id')) : Composer::bornToday()->inRandomOrder()->first();

    return new OnThisDay($composer, Subscription::admin());
})->name('birthday');