<?php

return [
    'user_agent' => env('WIKIMEDIA_USER_AGENT', 'PianoLITTimeline/1.0 (https://pianolit.com; admin timeline curation)'),
    'timeout' => 8,
    'query_timeout' => 20,
    'cache_minutes' => 360,
    'ranges' => [3, 7, 15, 25, 40],
];
