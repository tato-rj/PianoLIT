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

Route::prefix('users')->name('users.')->group(function() {

	Route::post('', 'UsersController@store')->name('store');

});

Route::prefix('pieces')->name('pieces.')->group(function() {

	Route::post('/views', 'PiecesController@incrementViews')->name('increment-views');

	Route::post('/find', 'ApiController@piece')->name('find');

});

Route::get('/discover', 'ApiController@discover')->name('discover');

Route::get('/search', 'ApiController@search')->name('search');

Route::get('/tour', 'ApiController@tour')->name('tour');