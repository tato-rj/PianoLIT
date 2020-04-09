<?php

Route::get('', 'Admin\AdminsController@home')->name('home');

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
