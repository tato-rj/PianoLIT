<?php

// Web-only presentation. Playlist contents continue to be maintained in admin.
// Match by normalized name so local/production database IDs need not agree.
return [
    'featured' => 'lullabies',
    'inspiration' => ['great-for-beginners', 'hidden-gems', 'burgmullers-studies'],
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
