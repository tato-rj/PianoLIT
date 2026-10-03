<?php

// Web-only presentation. Playlist contents continue to be maintained in admin.
// Match by normalized name so local/production database IDs need not agree.
return [
    // Covers always come from the existing admin upload. Only browse categories live here.
    'categories' => [
        'lullabies' => 'mood',
        'at-night' => 'mood',
        'sunday-morning' => 'mood',
        'hidden-gems' => 'other',
        'great-for-beginners' => 'level',
        'burgmullers-studies' => 'composer',
        'its-raining-outside' => 'mood',
        'dark-vibes' => 'mood',
        'peaceful-dreamin' => 'mood',
        'melancholic-mood' => 'mood',
        'wild-flowers' => 'mood',
        'on-a-winter-day' => 'mood',
        'fantastic-places' => 'mood',
        'true-romance' => 'mood',
    ],
    // These are announcements, not empty playlists or fabricated progress.
    'books' => [
        ['number' => 1, 'level' => 'Elementary', 'image' => 'book-1'],
        ['number' => 2, 'level' => 'Elementary to beginner', 'image' => 'book-2'],
        ['number' => 3, 'level' => 'Beginner', 'image' => 'book-3'],
        ['number' => 4, 'level' => 'Beginner to intermediate', 'image' => 'book-4'],
        ['number' => 5, 'level' => 'Intermediate', 'image' => 'book-5'],
        ['number' => 6, 'level' => 'Intermediate', 'image' => 'book-6'],
        ['number' => 7, 'level' => 'Intermediate to advanced', 'image' => 'book-7'],
        ['number' => 8, 'level' => 'Advanced', 'image' => 'book-8'],
    ],
];
