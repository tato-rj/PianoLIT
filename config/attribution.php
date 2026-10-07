<?php

return [
    // Deploy the migration and review the opt-in wording before enabling.
    'enabled' => env('CAMPAIGN_ATTRIBUTION_ENABLED', false),
    'touch_days' => 30,
    'retention_days' => 90,
    'consent_version' => 'v1',
    // Store these fixed identifiers, never arbitrary URL/query/UTM values.
    'campaigns' => [
        'respighi_notturno' => [
            'piece_id' => 174,
            'utm_source' => 'youtube',
            'utm_medium' => 'organic_video',
            'utm_campaign' => 'respighi_notturno',
            'utm_content' => 'description',
        ],
    ],
];
