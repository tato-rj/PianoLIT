<?php

return [
    // Prepare private bounded assets before enabling native tours. Web is unaffected.
    'enabled' => env('API_V2_MATCH_TOUR_ENABLED', false),
    'media_root' => storage_path('app/api-v2-match-media'),
    'ffmpeg' => env('MATCH_TOUR_FFMPEG', 'ffmpeg'),
    'ffprobe' => env('MATCH_TOUR_FFPROBE', 'ffprobe'),
    'pdftoppm' => env('MATCH_TOUR_PDFTOPPM', 'pdftoppm'),
];
