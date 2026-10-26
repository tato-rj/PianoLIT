@extends('layouts.app', ['title' => 'PianoLIT Learn | Tools, courses & discoveries', 'bodyClass' => 'learn-home'])

@section('content')
@php
    $sections = [
        [
            'id' => 'tools', 'label' => 'Interactive tools',
            'description' => 'See how music works. Explore keys and chords by trying them yourself.',
            'projects' => [
                ['key' => 'circle', 'title' => 'Circle of fifths', 'format' => 'Explore harmony', 'route' => 'tools.circle-of-fifths', 'description' => 'See how keys connect, explore key signatures, and find your way around harmony.', 'action' => 'Explore the circle'],
                ['key' => 'chords', 'title' => 'Chord finder', 'format' => 'Find an answer', 'route' => 'tools.chord-finder.index', 'description' => 'Choose notes on a piano keyboard and discover the chords they make.', 'action' => 'Find a chord'],
            ],
        ],
        [
            'id' => 'guides', 'label' => 'Courses & guides',
            'description' => 'Take it one idea at a time, with lessons to follow and references to keep.',
            'projects' => [
                ['key' => 'courses', 'title' => 'Crash courses', 'format' => 'Lessons by email', 'route' => 'crashcourses.index', 'description' => 'Go deeper into a musical topic with a series of short lessons delivered to your inbox.', 'action' => 'Browse courses'],
                ['key' => 'infographics', 'title' => 'Infographics', 'format' => 'Learn visually', 'route' => 'resources.infographs.index', 'description' => 'Make complex ideas click with clear visual guides you can download and revisit.', 'action' => 'Explore the guides'],
            ],
        ],
        [
            'id' => 'discover', 'label' => 'Quizzes & pianists',
            'description' => 'Follow your curiosity. Challenge what you know or discover someone inspiring.',
            'projects' => [
                ['key' => 'quizzes', 'title' => 'Quizzes', 'format' => 'Test your knowledge', 'route' => 'quizzes.index', 'description' => 'Put your musical knowledge to the test and learn something along the way.', 'action' => 'Try a quiz'],
                ['key' => 'pianists', 'title' => 'Great pianists', 'format' => 'Meet the artists', 'route' => 'resources.pianists.index', 'description' => 'Get to know the remarkable pianists who have shaped the way we hear and play music.', 'action' => 'Discover the pianists'],
            ],
        ],
    ];
@endphp

<div class="container py-4 pb-5">
    @include('components.navigation.breadcrumb', ['items' => [
        ['label' => 'Home', 'url' => route('home')], ['label' => 'Learn'],
    ]])

    @component('components.layout.hub-header', ['heading' => 'PianoLIT Learn', 'preview' => false])
        Find a chord. Explore a key. Take a short course.<br class="d-none d-md-block"> Tools, visual guides, quizzes, and great pianists to learn from.
    @endcomponent

    @component('components.layout.editorial')
        @foreach($sections as $section)
            <section id="{{ $section['id'] }}" class="learn-home__section mb-5" aria-labelledby="learn-{{ $section['id'] }}-title">
                <div class="mb-4">
                    <h2 id="learn-{{ $section['id'] }}-title" class="h4 mb-2">{{ $section['label'] }}</h2>
                    <p class="text-muted small mb-0">{{ $section['description'] }}</p>
                </div>
                <div class="row g-4">
                    @foreach($section['projects'] as $project)
                        <div class="col-md-6">
                            @component('components.cards.resource', [
                                'id' => 'learn-'.$project['key'], 'title' => $project['title'],
                                'description' => $project['description'], 'format' => $project['format'],
                                'url' => route($project['route']), 'action' => $project['action'],
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

        @slot('sidebar')
            @include('components.promotions.ads', ['ads' => ['crashcourse', 'ebook', 'escore'], 'variant' => 'cards'])
        @endslot
    @endcomponent
</div>
@endsection
