<?php

return [
    'enabled' => env('APPLE_MEMBERSHIP_MONITOR_ENABLED', true),
    // Optional server-side override when Apple's app-specific shared secret rotates.
    'shared_secret' => env('APPLE_MEMBERSHIP_SHARED_SECRET'),
    'bundle_id' => env('APPLE_MEMBERSHIP_BUNDLE_ID', 'com.leftlaneapps.PianoLIT'),
    // Existing administrative receipt checks excluded these test accounts.
    'excluded_user_ids' => [284, 260, 249, 196],
];
