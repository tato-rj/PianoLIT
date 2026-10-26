<section id="explore" class="support-section" aria-labelledby="explore-heading">
    <h2 id="explore-heading">Explore PianoLIT</h2>
    <p class="text-muted">Start with the destination that fits what you want to do.</p>
    <div class="border-top">
        @foreach([
            ['title' => 'Web app', 'icon' => 'pianolit', 'description' => 'Discover piano repertoire, watch performances and tutorials, read scores, and organize pieces into favorite folders in your browser.', 'url' => route('webapp.discover'), 'destination' => 'my.'.config('app.short_url')],
            ['title' => 'iOS app', 'icon' => 'brand-apple', 'description' => 'Explore PianoLIT on iPhone and iPad. Sign in to your PianoLIT account; app features and layout may differ from the web app.', 'url' => config('app.stores.ios'), 'destination' => 'App Store'],
            ['title' => 'Learn', 'icon' => 'graduation-cap', 'description' => 'Explore the Circle of Fifths, Chord Finder, scales, arpeggios, crash courses, infographics, quizzes, and great pianists.', 'url' => route('learn.home'), 'destination' => 'learn.'.config('app.short_url')],
            ['title' => 'Blog', 'icon' => 'newspaper', 'description' => 'Read articles about piano repertoire, composers, practice, and music.', 'url' => route('posts.index'), 'destination' => 'blog.'.config('app.short_url')],
            ['title' => 'Shop', 'icon' => 'shopping-basket', 'description' => 'Find our eBook and eScore catalogs. The shop homepage is coming soon; the existing catalogs remain available.', 'url' => route('shop.home'), 'destination' => 'shop.'.config('app.short_url')],
            ['title' => 'Games', 'icon' => 'trophy', 'description' => 'A home for musical play. This hub is coming soon.', 'url' => route('games.home'), 'destination' => 'games.'.config('app.short_url')],
            ['title' => 'Metronome', 'icon' => 'metronome', 'description' => 'Keep time with adjustable tempo, different sounds, and a visual conductor. Sign in to save your own counting voices.', 'url' => route('metronome.home'), 'destination' => 'metronome.'.config('app.short_url')],
        ] as $hub)
            <div class="support-hub row g-3 py-3 border-bottom align-items-start">
                <div class="col-sm-4">
                    <a class="d-inline-flex align-items-center text-body fw-semibold" href="{{ $hub['url'] }}" @if($hub['title'] === 'iOS app') target="_blank" rel="noopener noreferrer" @endif>
                        @icon($hub['icon'], ['mr' => 2]){{ $hub['title'] }} @icon('arrow-up-right', ['mr' => 0, 'ml' => 2, 'classes' => 'text-muted'])
                    </a>
                    <p class="small text-muted mb-0 mt-1 text-break">{{ $hub['destination'] }}</p>
                </div>
                <div class="col-sm-8"><p class="mb-0">{{ $hub['description'] }}</p></div>
            </div>
        @endforeach
    </div>
</section>
