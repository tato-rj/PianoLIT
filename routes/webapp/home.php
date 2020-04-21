<?php

Route::get('', function() {
	return 'Subdomain configured';
});

Route::get('foo', function() {
	return 'bar';
});