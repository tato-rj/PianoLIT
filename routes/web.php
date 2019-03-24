<?php

Route::get('/', function () {
    return view('landing-page.index');
});

Route::get('/home', 'HomeController@index')->name('home');
