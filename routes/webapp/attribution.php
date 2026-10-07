<?php

Route::post('attribution/consent', 'WebApp\CampaignAttributionController@consent')
    ->middleware('throttle:20,1')
    ->withoutMiddleware(['log.webapp', 'location.update'])
    ->name('attribution.consent');
