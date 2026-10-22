@extends('layouts.app', ['title' => 'PianoLIT Learn | Tools, courses & discoveries', 'bodyClass' => 'learn-home'])

@section('content')
@php
    $sections = [
        [
            'id' => 'tools', 'label' => 'Interactive tools', 'nav' => 'Interactive tools',
            'description' => 'See how music works. Explore keys and chords by trying them yourself.',
            'projects' => [
                ['key' => 'circle', 'title' => 'Circle of fifths', 'format' => 'Explore harmony', 'color' => 'mint', 'route' => 'tools.circle-of-fifths', 'description' => 'See how keys connect, explore key signatures, and find your way around harmony.', 'action' => 'Explore the circle'],
                ['key' => 'chords', 'title' => 'Chord finder', 'format' => 'Find an answer', 'color' => 'sky', 'route' => 'tools.chord-finder.index', 'description' => 'Choose notes on a piano keyboard and discover the chords they make.', 'action' => 'Find a chord'],
            ],
        ],
        [
            'id' => 'guides', 'label' => 'Courses & guides', 'nav' => 'Courses & guides',
            'description' => 'Take it one idea at a time, with lessons to follow and references to keep.',
            'projects' => [
                ['key' => 'courses', 'title' => 'Crash courses', 'format' => 'Lessons by email', 'color' => 'peach', 'route' => 'crashcourses.index', 'description' => 'Go deeper into a musical topic with a series of short lessons delivered to your inbox.', 'action' => 'Browse courses'],
                ['key' => 'infographics', 'title' => 'Infographics', 'format' => 'Learn visually', 'color' => 'lavender', 'route' => 'resources.infographs.index', 'description' => 'Make complex ideas click with clear visual guides you can download and revisit.', 'action' => 'Explore the guides'],
            ],
        ],
        [
            'id' => 'discover', 'label' => 'Quizzes & pianists', 'nav' => 'Quizzes & pianists',
            'description' => 'Follow your curiosity. Challenge what you know or discover someone inspiring.',
            'projects' => [
                ['key' => 'quizzes', 'title' => 'Quizzes', 'format' => 'Test your knowledge', 'color' => 'butter', 'route' => 'quizzes.index', 'description' => 'Put your musical knowledge to the test and learn something along the way.', 'action' => 'Try a quiz'],
                ['key' => 'pianists', 'title' => 'Great pianists', 'format' => 'Meet the artists', 'color' => 'rose', 'route' => 'resources.pianists.index', 'description' => 'Get to know the remarkable pianists who have shaped the way we hear and play music.', 'action' => 'Discover the pianists'],
            ],
        ],
    ];
@endphp

<div class="container py-4 pb-5">
    @include('components.breadcrumb', ['items' => [
        ['label' => 'Home', 'url' => route('home')], ['label' => 'Learn'],
    ]])

    <header class="row align-items-end gy-4 mb-4 learn-home__intro">
        <div class="col-lg-8">
            <h1 class="mb-3">PianoLIT Learn</h1>
            <p class="text-muted mb-0">Interactive tools, short lessons, and fresh discoveries. <br class="d-none d-md-block">Understand the music you play, one idea at a time.</p>
        </div>
        <div class="col-lg-4 text-lg-end">
            @include('components.newsletter.subscribe-button', ['label' => 'Join the newsletter', 'preview' => false])
        </div>
    </header>

    <nav class="d-flex flex-wrap gap-2 border-top border-bottom py-4 mb-4" aria-label="Explore Learn">
        @foreach($sections as $section)
            <a href="#{{ $section['id'] }}" class="btn btn-secondary btn-sm">{{ $section['nav'] }}</a>
        @endforeach
    </nav>

    @component('components.editorial-layout')
        @foreach($sections as $section)
            <section id="{{ $section['id'] }}" class="learn-home__section mb-5" aria-labelledby="learn-{{ $section['id'] }}-title">
                <div class="mb-4">
                    <h2 id="learn-{{ $section['id'] }}-title" class="h4 mb-2">{{ $section['label'] }}</h2>
                    <p class="text-muted small mb-0">{{ $section['description'] }}</p>
                </div>
                <div class="row g-4">
                    @foreach($section['projects'] as $project)
                        <div class="col-md-6">
                            @component('components.resource-card', [
                                'id' => 'learn-'.$project['key'], 'title' => $project['title'],
                                'description' => $project['description'], 'format' => $project['format'],
                                'color' => $project['color'], 'url' => route($project['route']), 'action' => $project['action'],
                            ])
                                @slot('artwork')
                                    @include('learn.components.artwork', ['kind' => $project['key']])
                                @endslot
                            @endcomponent
                        </div>
                    @endforeach
                </div>
                @if($section['id'] === 'tools')
                    <div class="d-flex flex-wrap align-items-center gap-2 mt-4">
                        <span class="text-muted small me-1">More practice tools</span>
                        <a href="{{ route('tools.scales.index') }}" class="btn btn-secondary btn-sm">Scales @icon('arrow-right', ['mr' => 0, 'ml' => 2])</a>
                        <a href="{{ route('tools.arpeggios.index') }}" class="btn btn-secondary btn-sm">Arpeggios @icon('arrow-right', ['mr' => 0, 'ml' => 2])</a>
                    </div>
                @endif
            </section>
        @endforeach

        @include('components.newsletter.invitation', ['headingId' => 'learn-newsletter-title', 'preview' => false])

        @slot('sidebar')
            <div class="mb-4">
                <h2 class="h5 mb-2">Explore PianoLIT</h2>
                <p class="text-muted small mb-0">Keep your curiosity playing.</p>
            </div>
            @include('components.display.ads', ['ads' => ['crashcourse', 'ebook', 'escore'], 'variant' => 'cards'])
        @endslot
    @endcomponent
</div>
@endsection
