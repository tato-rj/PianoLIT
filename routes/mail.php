<?php

use App\Mail\Welcome;
use App\Mail\Timeline\OnThisDay;
use App\{Composer, Subscription};

Route::prefix('birthday')->name('birthday.')->group(function() {

	Route::get('', function () {
		$composer = request()->has('composer_id') ? Composer::find(request('composer_id')) : Composer::bornToday()->inRandomOrder()->first();

	    return new OnThisDay($composer, Subscription::admin());
	})->name('web');

	Route::get('/{composer_id}', function () {
		$email = new OnThisDay(Composer::find(request('composer_id')), Subscription::admin());

		\Mail::to(Subscription::admin())->send($email);

	    return $email;
	})->name('mail');
	
});
