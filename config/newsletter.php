<?php

return [
    // Add an entry alongside each code-based feature, game or tool launch.
    // Database-backed hub content is collected from its publication date.
    // Keys must remain stable so a queued newsletter can recheck availability.
    // 'chord-finder-v2' => [
    //     'title' => 'A new way to explore chords',
    //     'description' => 'A short, reader-facing explanation of the new feature.',
    //     'hub' => 'Learn', 'type' => 'Tool',
    //     'route' => 'tools.chord-finder.index', 'parameters' => [],
    //     'published_at' => '2026-12-01 09:00:00', // America/New_York
    // ],
    'releases' => [
        'games-2026' => [
            'title' => 'Play your way through music theory',
            'description' => 'Explore eleven music theory games, compare your scores and challenge a friend to a multiplayer duel.',
            'hub' => 'Games', 'type' => 'Games',
            'route' => 'games.home', 'parameters' => [],
            'published_at' => '2026-10-26 13:00:00',
        ],
        'metronome-own-voice-2026' => [
            'title' => 'Let your own voice keep the beat',
            'description' => 'Record four quick counts, then practice with your own voice in the PianoLIT Metronome. Your recording is saved to your account.',
            'hub' => 'Metronome', 'type' => 'Feature',
            'route' => 'metronome.home', 'parameters' => [],
            'published_at' => '2026-10-22 15:00:00',
        ],
        'metronome-2026' => [
            'title' => 'Find your rhythm with the PianoLIT Metronome',
            'description' => 'Tap to play, swipe to set your tempo, or follow the conductor. Choose your beat count, sound and color.',
            'hub' => 'Metronome', 'type' => 'Tool',
            'route' => 'metronome.home', 'parameters' => [],
            'published_at' => '2026-10-22 13:00:00',
        ],
    ],
];
