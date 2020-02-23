<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::prefix('pieces')->name('pieces.')->group(function() {

	Route::post('/views', 'PieceViewsController@store')->name('views.store');

	Route::post('/find', 'ApiController@piece')->name('find');

	Route::get('{piece}/timeline', 'ApiController@timeline')->name('timeline');

	Route::get('{piece}/collection', 'PiecesController@collection')->name('collection');

	Route::get('{piece}/similar', 'PiecesController@similar')->name('similar');
});

Route::prefix('playlists')->name('playlists.')->group(function() {

	Route::get('{group}', 'ApiController@playlists')->name('all');

	Route::get('{playlist}/pieces', 'ApiController@playlist')->name('show');

});

Route::prefix('blog')->name('blog.')->group(function() {

	Route::get('/search', 'PostsController@search')->name('search');

});

Route::prefix('subscriptions')->name('subscriptions.')->group(function() {

	Route::post('/unsubscribe', 'SubscriptionsController@unsubscribe')->name('unsubscribe');

});

Route::prefix('users')->name('users.')->group(function() {

	Route::prefix('favorites')->name('favorites.')->group(function() {
	
		Route::post('/update', 'FavoritesController@update')->name('update');
		
		Route::post('/show', 'FavoritesController@show')->name('show');

	});

	Route::prefix('tutorial-requests')->name('tutorial-requests.')->group(function() {

		Route::get('', 'TutorialRequestsController@index')->name('index');

		Route::post('', 'TutorialRequestsController@store')->name('store');

		Route::delete('/cancel', 'TutorialRequestsController@destroy')->name('destroy');

	});

	Route::post('', 'Auth\RegisterController@register')->name('store');

	Route::post('/login', 'UsersController@appLogin')->name('login');

	Route::get('{user}', 'ApiController@user')->name('show');

	Route::post('/suggestions', 'ApiController@suggestions')->name('suggestions');

});

Route::prefix('memberships')->name('memberships.')->group(function() {

	Route::post('', 'MembershipsController@store')->name('store');

	Route::post('history', 'MembershipsController@history')->name('history');

	Route::post('status', 'MembershipsController@status')->name('status');

});

Route::get('discover', 'ApiController@discover')->name('discover');

Route::get('search', 'SearchController@search')->name('search');

Route::get('tour', 'ApiController@tour')->name('tour');

Route::get('tags', 'ApiController@tags')->name('tags');

Route::get('composers', 'ApiController@composers')->name('composers');

Route::get('users', 'ApiController@users')->name('users');

Route::get('tutorial-requests', 'TutorialRequestsController@api')->name('tutorial-requests');