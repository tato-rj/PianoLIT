<?php

Route::name('tools.')->group(function () {
    Route::get('circle-of-fifths', 'ToolsController@circleOfFifths')->name('circle-of-fifths');

    Route::prefix('chord-finder')->name('chord-finder.')->group(function () {
        Route::get('', 'ToolsController@chordFinder')->name('index');
        Route::get('analyse', 'ToolsController@analyseChord')->name('analyse');
    });

    Route::prefix('scales')->name('scales.')->group(function () {
        Route::get('', 'ToolsController@scales')->name('index');
        Route::get('generate', 'ToolsController@generateScale')->name('generate');
    });

    Route::prefix('arpeggios')->name('arpeggios.')->group(function () {
        Route::get('', 'ToolsController@arpeggios')->name('index');
        Route::get('generate', 'ToolsController@generateArpeggio')->name('generate');
    });
});

Route::prefix('crashcourses')->name('crashcourses.')->group(function () {
    Route::get('', 'CrashCoursesController@index')->name('index');
    Route::get('video', 'CrashCoursesController@video')->name('video');
    Route::get('cancel', 'CrashCoursesController@cancel')->name('cancel');
    Route::get('{crashcourse}', 'CrashCoursesController@show')->name('show');
    Route::post('{crashcourse}/signup', 'CrashCoursesController@signup')->name('signup');
});

Route::prefix('infographics')->group(function () {
    Route::get('', 'InfographicsController@index')->name('resources.infographs.index');
    Route::get('{infograph}', 'InfographicsController@show')->name('resources.infographs.show');
    Route::get('{infograph}/download', 'InfographicsController@download')->middleware('auth')->name('infographs.download');
    Route::post('{infograph}/update-score', 'InfographicsController@updateScore')->name('infographs.update-score');
});

Route::prefix('great-pianists')->name('resources.pianists.')->group(function () {
    Route::get('', 'PianistsController@index')->name('index');
    Route::get('{pianist}', 'PianistsController@show')->name('show');
});

Route::prefix('quizzes')->name('quizzes.')->group(function () {
    Route::get('', 'QuizzesController@index')->name('index');
    Route::get('{quiz}', 'QuizzesController@show')->name('show');
    Route::get('{quiz}/feedback', 'QuizzesController@feedback')->name('feedback');
});
