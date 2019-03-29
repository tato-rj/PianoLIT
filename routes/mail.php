<?php

Route::get('newsletter/welcome', function() {
	return new \App\Mail\Newsletter\Welcome;
});