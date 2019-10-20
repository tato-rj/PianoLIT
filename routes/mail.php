<?php

use App\Mail\Welcome;
use App\Mail\Timeline\OnThisDay;
use App\{Composer, Subscription};

Route::get('welcome', function() {
	return new Welcome;
});

Route::get('onthisday', function () {
    return new OnThisDay(Composer::bornToday()->inRandomOrder()->first(), Subscription::admin());
});