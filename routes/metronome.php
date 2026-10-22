<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'metronome.index')->name('metronome.home');

Route::middleware('auth:web')->prefix('voice')->name('metronome.voice.')->group(function () {
    Route::get('record', 'MetronomeVoiceController@start')->name('start');
    Route::get('/', 'MetronomeVoiceController@show')->middleware('throttle:60,1')->name('show');
    Route::put('/', 'MetronomeVoiceController@store')->middleware('throttle:12,1')->name('store');
    Route::get('{voice}', 'MetronomeVoiceController@recording')->whereUuid('voice')->middleware('throttle:60,1')->name('recording');
    Route::patch('{voice}', 'MetronomeVoiceController@rename')->whereUuid('voice')->middleware('throttle:12,1')->name('rename');
    Route::delete('{voice}', 'MetronomeVoiceController@destroy')->whereUuid('voice')->middleware('throttle:12,1')->name('destroy');
});
