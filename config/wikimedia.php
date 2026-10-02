<?php

return [
    'user_agent' => env('WIKIMEDIA_USER_AGENT', 'PianoLIT/1.0 (https://pianolit.com; contact@pianolit.com; timeline curation)'),
    'timeout' => 8,
    'query_timeout' => 8,
    'discovery_budget' => 25,
    'cache_minutes' => 360,
    'library_range' => 10,
    'candidate_limit' => 40,
    'shortlist_per_property' => 10,
    'max_batches' => 24, // Four shortlists per page, at most six pages.
];
