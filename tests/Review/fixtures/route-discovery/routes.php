<?php

\Illuminate\Support\Facades\Route::get('/review/route-discovery', function () {
    return 'Route loaded';
})->name('review.route-discovery');
