<?php

Route::middleware('api.chatgpt')->prefix('chatgpt')->group(function() {
	
	Route::get('composers', 'Api\ChatGPTController@composers');

	Route::get('pieces', 'Api\ChatGPTController@pieces');

});
