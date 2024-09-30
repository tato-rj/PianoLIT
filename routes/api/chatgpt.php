<?php

Route::prefix('chatgpt')->group(function() {
	
	Route::get('composers', 'Api\ChatGPTController@composers');

});
