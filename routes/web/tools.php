<?php

// Already-open tool pages keep their original same-origin AJAX endpoints.
Route::get('tools/chord-finder/analyse', 'ToolsController@analyseChord')->name('legacy.tools.chord-finder.analyse');
Route::get('tools/scales/generate', 'ToolsController@generateScale')->name('legacy.tools.scales.generate');
Route::get('tools/arpeggios/generate', 'ToolsController@generateArpeggio')->name('legacy.tools.arpeggios.generate');

Route::get('tools/{tool}/{path?}', 'RedirectsController@learn')
    ->where('tool', 'circle-of-fifths|chord-finder|scales|arpeggios')
    ->where('path', '.*')
    ->name('legacy.tools');
