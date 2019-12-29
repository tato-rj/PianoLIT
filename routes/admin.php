<?php

Route::get('', 'AdminsController@home')->name('home');

Route::post('logout', 'Auth\Admin\LoginController@logout')->name('logout');

Route::resources([
    'pieces' => 'PiecesController',
    'composers' => 'ComposersController',
    'tags' => 'TagsController',
    'topics' => 'TopicsController',
    'editors' => 'EditorsController',
    'timelines' => 'TimelinesController',
    'pianists' => 'PianistsController',
    'playlists' => 'PlaylistsController'
]);

Route::patch('composers/{composer}/toggle-famous', 'ComposersController@toggleFamous')->name('composers.toggle-famous');

Route::prefix('notifications')->name('notifications.')->group(function() {

	Route::get('', 'AdminsController@notifications')->name('index');

	Route::get('read', 'NotificationsController@read')->name('read');

	Route::get('unread', 'NotificationsController@unread')->name('unread');

});

Route::prefix('infographs')->name('infographs.')->group(function() {
	
	Route::get('', 'InfographsController@index')->name('index');

	Route::get('create', 'InfographsController@create')->name('create');

	Route::post('', 'InfographsController@store')->name('store');

	Route::prefix('topics')->name('topics.')->group(function() {
	
		Route::get('', 'InfographsController@topics')->name('index');

		Route::post('store', 'InfographsController@topicStore')->name('store');

		Route::patch('{topic}/update', 'InfographsController@topicUpdate')->name('update');
		
		Route::delete('{topic}/destroy', 'InfographsController@topicDestroy')->name('destroy');

	});

	Route::patch('{infograph}/status', 'InfographsController@updateStatus')->name('update-status');

	Route::get('{infograph}', 'InfographsController@edit')->name('edit');

	Route::patch('{infograph}', 'InfographsController@update')->name('update');

	Route::delete('{infograph}', 'InfographsController@destroy')->name('destroy');
});

Route::prefix('blog')->name('posts.')->group(function() {

	Route::get('', 'AdminsController@blog')->name('index');

	Route::get('create', 'PostsController@create')->name('create');

	Route::post('', 'PostsController@store')->name('store');

	Route::post('images/upload', 'PostsController@uploadImage')->name('upload-image');

	Route::post('images/remove', 'PostsController@removeImage')->name('remove-image');

	// Route::prefix('audio')->name('audio.')->group(function() {

	// 	Route::get('', 'BlogAudioController@index')->name('index');

	// 	Route::post('store', 'BlogAudioController@store')->name('store');
		
	// 	Route::delete('destroy', 'BlogAudioController@destroy')->name('destroy');

	// });

	Route::prefix('audio')->name('audio.')->group(function() {
	
		Route::get('', 'BlogMediaController@audio')->name('index');

		Route::post('store', 'BlogMediaController@storeAudio')->name('store');
		
		Route::delete('destroy', 'BlogMediaController@destroyAudio')->name('destroy');

	});

	Route::prefix('gifts')->name('gifts.')->group(function() {
	
		Route::get('', 'BlogMediaController@gifts')->name('index');

		Route::post('store', 'BlogMediaController@storeGift')->name('store');
		
		Route::delete('destroy', 'BlogMediaController@destroyGift')->name('destroy');

	});

	Route::get('{post}', 'PostsController@edit')->name('edit');

	Route::patch('{post}', 'PostsController@update')->name('update');

	Route::patch('{post}/status', 'PostsController@updateStatus')->name('update-status');

	Route::delete('{post}', 'PostsController@destroy')->name('destroy');

});

Route::prefix('quiz')->name('quizzes.')->group(function() {

	Route::get('', 'AdminsController@quiz')->name('index');

	Route::get('create', 'QuizzesController@create')->name('create');

	Route::post('', 'QuizzesController@store')->name('store');

	Route::post('images/upload', 'QuizzesController@uploadImage')->name('upload-image');

	Route::post('images/remove', 'QuizzesController@removeImage')->name('remove-image');

	Route::prefix('media')->name('media.')->group(function() {
	
		Route::get('audio', 'QuizMediaController@audio')->name('audio');

		Route::get('images', 'QuizMediaController@images')->name('images');

		Route::post('{type}/store', 'QuizMediaController@store')->name('store');
		
		Route::delete('destroy', 'QuizMediaController@destroy')->name('destroy');

	});
	
	Route::delete('destroy', 'QuizMediaController@destroy')->name('destroy');

	Route::prefix('topics')->name('topics.')->group(function() {
	
		Route::get('', 'AdminsController@quizTopics')->name('index');

		Route::post('store', 'QuizzesController@topicStore')->name('store');

		Route::patch('{topic}/update', 'QuizzesController@topicUpdate')->name('update');
		
		Route::delete('{topic}/destroy', 'QuizzesController@topicDestroy')->name('destroy');

	});

	Route::get('{quiz}', 'QuizzesController@edit')->name('edit');

	Route::patch('{quiz}', 'QuizzesController@update')->name('update');

	Route::patch('{quiz}/status', 'QuizzesController@updateStatus')->name('update-status');

	Route::delete('{quiz}', 'QuizzesController@destroy')->name('destroy');

});

Route::prefix('api')->name('api.')->group(function() {

	Route::get('discover', 'ApiController@discover')->name('discover');

	Route::get('search', 'SearchController@search')->name('search');

	Route::get('tour', 'ApiController@tour')->name('tour');

});

Route::prefix('subscriptions')->name('subscriptions.')->group(function() {

	Route::get('', 'AdminsController@subscriptions')->name('index');

	Route::get('export', 'SubscriptionsController@export')->name('export');

	Route::post('', 'Admin\SubscriptionsController@store')->name('store');

});

Route::prefix('statistics')->name('stats.')->group(function() {

	Route::get('users', 'StatsController@users')->name('users');

	Route::get('pieces', 'StatsController@pieces')->name('pieces');

	Route::get('composers', 'StatsController@composers')->name('composers');

	Route::get('blog', 'StatsController@blog')->name('blog');

	Route::get('quizzes', 'StatsController@quizzes')->name('quizzes');

	Route::get('infographs', 'StatsController@infographs')->name('infographs');

});


Route::prefix('users')->name('users.')->group(function() {

	Route::patch('{user}/super-status', 'MembershipsController@superStatus')->name('super-status');

	Route::get('', 'Admin\UsersController@index')->name('index');

	Route::get('{user}', 'Admin\UsersController@show')->name('show');

	Route::delete('destroy-many', 'Admin\UsersController@destroyMany')->name('destroy-many');

	Route::delete('{user}', 'Admin\UsersController@destroy')->name('destroy');

});

Route::prefix('memberships')->name('memberships.')->group(function() {

	Route::prefix('validate')->name('validate.')->group(function() {

		Route::get('', 'MembershipsController@validateAll')->name('all');

		Route::post('{user}', 'MembershipsController@validateUser')->name('user');
	
	});

	Route::delete('{user}', 'MembershipsController@destroy')->name('destroy');

});

Route::prefix('pieces')->name('pieces.')->group(function() {

	Route::post('/single-lookup', 'PiecesController@singleLookup')->name('single-lookup');
	
	Route::post('/multi-lookup', 'PiecesController@multiLookup')->name('multi-lookup');
	
	Route::post('/validate-name', 'PiecesController@validateName')->name('validate-name');

	Route::get('datatable', 'PiecesController@datatable')->name('datatable');

	Route::get('alerts/show', 'PiecesController@alerts')->name('alerts');

	Route::patch('{piece}/update-level', 'PiecesController@updateLevel')->name('update-level');

	Route::patch('{piece}/update-tag', 'PiecesController@updateTag')->name('update-tag');

	Route::patch('{piece}/highlight', 'HighlightsController@update')->name('highlight');

	Route::get('{piece}/load-tags', 'PiecesController@loadTags')->name('load-tags');

	Route::get('{piece}/load-levels', 'PiecesController@loadLevels')->name('load-levels');

});

Route::prefix('tutorial-requests')->name('tutorial-requests.')->group(function() {

	Route::get('', 'TutorialRequestsController@index')->name('index');

	Route::post('simulate', 'TutorialRequestsController@simulate')->name('simulate');
	
	Route::get('{tutorialRequest}', 'TutorialRequestsController@show')->name('show');

	Route::patch('{tutorialRequest}/publish', 'TutorialRequestsController@publish')->name('publish');

});
