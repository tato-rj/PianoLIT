<?php

// Web-only presentation. Playlist contents continue to be maintained in admin.
// Match by normalized name so local/production database IDs need not agree.
return [
    'featured' => 'lullabies',
    'inspiration' => ['great-for-beginners', 'hidden-gems', 'burgmullers-studies'],
    'artwork' => [
        'lullabies' => ['image' => 'night', 'category' => 'mood'],
        'at-night' => ['image' => 'night', 'category' => 'mood'],
        'sunday-morning' => ['image' => 'morning', 'category' => 'mood'],
        'hidden-gems' => ['image' => 'botanical', 'category' => 'other'],
        'great-for-beginners' => ['image' => 'steps', 'category' => 'level'],
        'burgmullers-studies' => ['image' => 'melody', 'category' => 'composer'],
        'its-raining-outside' => ['image' => 'night', 'category' => 'mood'],
        'dark-vibes' => ['image' => 'night', 'category' => 'mood'],
        'peaceful-dreamin' => ['image' => 'melody', 'category' => 'mood'],
        'melancholic-mood' => ['image' => 'night', 'category' => 'mood'],
        'wild-flowers' => ['image' => 'botanical', 'category' => 'mood'],
        'on-a-winter-day' => ['image' => 'night', 'category' => 'mood'],
        'fantastic-places' => ['image' => 'steps', 'category' => 'mood'],
        'true-romance' => ['image' => 'melody', 'category' => 'mood'],
    ],
    // These are announcements, not empty playlists or fabricated progress.
    'books' => [
        ['number' => 1, 'level' => 'Late elementary to beginner', 'image' => 'book-1'],
        ['number' => 2, 'level' => 'Beginner to early intermediate', 'image' => 'book-2'],
        ['number' => 3, 'level' => 'Beginner to early intermediate', 'image' => 'book-3'],
        ['number' => 4, 'level' => 'Late intermediate to early advanced', 'image' => 'book-4'],
        ['number' => 5, 'level' => 'Early advanced', 'image' => 'book-5'],
        ['number' => 6, 'level' => 'Advanced', 'image' => 'book-6'],
        ['number' => 7, 'level' => 'Advanced', 'image' => 'book-7'],
        ['number' => 8, 'level' => 'Advanced', 'image' => 'book-8'],
    ],
];
