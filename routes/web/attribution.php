<?php

Route::post('attribution/consent', 'WebApp\CampaignAttributionController@consent')
    ->middleware('throttle:20,1')
    ->withoutMiddleware(['log.web', 'location.update'])
    ->name('attribution.consent');
