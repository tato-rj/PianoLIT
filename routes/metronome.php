<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'metronome.index')->name('metronome.home');

Route::middleware('auth:web')->prefix('voice')->name('metronome.voice.')->group(function () {
    Route::get('record', 'MetronomeVoiceController@start')->name('start');
    Route::get('/', 'MetronomeVoiceController@show')->middleware('throttle:60,1')->name('show');
    Route::put('/', 'MetronomeVoiceController@store')->middleware('throttle:12,1')->name('store');
    Route::delete('/', 'MetronomeVoiceController@destroy')->middleware('throttle:12,1')->name('destroy');
});
