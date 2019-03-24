<?php

Route::get('/', function () {
    return view('welcome.index');
})->name('home');
