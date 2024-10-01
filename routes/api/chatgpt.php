<?php

Route::middleware('api.chatgpt')->prefix('chatgpt')->group(function() {
	
	Route::get('composer', 'Api\ChatGPTController@composer');

});
