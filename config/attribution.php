<?php

return [
    // Deploy both campaign migrations before serving this feature.
    'enabled' => env('CAMPAIGN_ATTRIBUTION_ENABLED', true),
    'touch_days' => 30,
    'retention_days' => 90,
    'consent_version' => 'v1',
];
