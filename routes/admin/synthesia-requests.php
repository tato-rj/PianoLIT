<?php

Route::prefix('synthesia-requests')->name('synthesia-requests.')->group(function () {
    Route::get('', 'Admin\SynthesiaRequestsController@index')->name('index');
    Route::patch('{synthesiaRequest}/publish', 'Admin\SynthesiaRequestsController@publish')->name('publish');
    Route::delete('{synthesiaRequest}', 'Admin\SynthesiaRequestsController@destroy')->name('destroy');
});

// Existing bookmarks/notifications and already-open forms retain their destinations.
Route::get('tutorial-requests', 'Admin\SynthesiaRequestsController@legacyIndex')->name('tutorial-requests.index');
Route::patch('tutorial-requests/{synthesiaRequest}/publish', 'Admin\SynthesiaRequestsController@publish')->name('tutorial-requests.publish');
Route::delete('tutorial-requests/{synthesiaRequest}', 'Admin\SynthesiaRequestsController@destroy')->name('tutorial-requests.destroy');
