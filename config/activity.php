<?php

return [
    'enabled' => env('ACTIVITY_ENABLED', true),
    'excluded_emails' => ['arthurvilar@gmail.com', 'elena.dare@gmail.com'],
    'visit_idle_minutes' => 30,
    'page_token_hours' => 24,
    'batch_size' => 50,
    'filter_bots' => env('ACTIVITY_FILTER_BOTS', true),
    'minimum_visit_seconds' => env('ACTIVITY_MINIMUM_VISIT_SECONDS', 5),
    'max_browser_events_per_page' => 2000,
];
