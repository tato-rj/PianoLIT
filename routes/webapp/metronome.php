<?php

Route::namespace('WebApp')->prefix('metronome')->name('metronome.')->middleware('auth:web')->group(function () {
    Route::get('preferences', 'MetronomePreferencesController@show')->name('preferences.show');
    Route::patch('preferences', 'MetronomePreferencesController@update')->name('preferences.update');
});
