<?php

Route::get('youtube', function() {
	return redirect(config('services.channels.youtube'));
})->name('youtube');

Route::get('download/ios', function() {
	return redirect(config('app.stores.ios'));
})->name('download.ios');