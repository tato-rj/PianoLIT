<?php

Route::get('', 'Admin\AdminsController@home')->name('home');

Route::post('logout', 'Auth\Admin\LoginController@logout')->name('logout');

Route::resources([
    'pieces' => 'Admin\PiecesController',
    'composers' => 'Admin\ComposersController',
    'tags' => 'Admin\TagsController',
    'topics' => 'Admin\TopicsController',
    'editors' => 'Admin\EditorsController',
    'timelines' => 'Admin\TimelinesController',
    'pianists' => 'Admin\PianistsController',
    'playlists' => 'Admin\PlaylistsController'
]);

Route::patch('composers/{composer}/toggle-famous', 'ComposersController@toggleFamous')->name('composers.toggle-famous');

Route::get('logs/data', 'Admin\LogsController@data')->name('log.data');

Route::get('memberships/logs', 'MembershipsController@logs')->name('memberships.logs');
