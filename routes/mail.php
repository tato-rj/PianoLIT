<?php

use App\Mail\Welcome;
use App\Mail\Timeline\OnThisDay;
use App\Composer;

Route::get('welcome', function() {
	return new Welcome;
});

Route::get('onthisday', function () {
    return new OnThisDay(Composer::bornToday()->inRandomOrder()->first());
});