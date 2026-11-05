@extends('layouts.app', ['title' => 'PianoLIT Learn | Tools, courses & guides', 'bodyClass' => 'learn-home'])

@section('content')
@php
    $sections = [
        [
            'id' => 'tools', 'label' => 'Interactive tools',
            'description' => 'Study keys and chords with interactive tools.',
            'projects' => [
                ['key' => 'circle', 'title' => 'Circle of fifths', 'format' => 'Explore harmony', 'route' => 'tools.circle-of-fifths', 'description' => 'Compare keys, key signatures, and scales.', 'action' => 'Explore the circle'],
                ['key' => 'chords', 'title' => 'Chord finder', 'format' => 'Find an answer', 'route' => 'tools.chord-finder.index', 'description' => 'Choose notes on a piano keyboard to find possible chords.', 'action' => 'Find a chord'],
            ],
        ],
        [
            'id' => 'guides', 'label' => 'Courses & guides',
            'description' => 'Read email lessons and download visual reference guides.',
            'projects' => [
                ['key' => 'courses', 'title' => 'Crash courses', 'format' => 'Lessons by email', 'route' => 'crashcourses.index', 'description' => 'Study a music topic through short lessons delivered by email.', 'action' => 'Browse courses'],
                ['key' => 'infographics', 'title' => 'Infographics', 'format' => 'Learn visually', 'route' => 'resources.infographs.index', 'description' => 'Download visual guides to music theory and piano practice.', 'action' => 'Explore the guides'],
            ],
        ],
        [
            'id' => 'discover', 'label' => 'Quizzes & pianists',
            'description' => 'Test your music knowledge and read about pianists.',
            'projects' => [
                ['key' => 'quizzes', 'title' => 'Quizzes', 'format' => 'Test your knowledge', 'route' => 'quizzes.index', 'description' => 'Answer questions about music theory, history, and piano.', 'action' => 'Try a quiz'],
                ['key' => 'pianists', 'title' => 'Great pianists', 'format' => 'Meet the artists', 'route' => 'resources.pianists.index', 'description' => 'Read biographies of pianists and learn about their careers.', 'action' => 'Discover the pianists'],
            ],
        ],
    ];
@endphp

<div class="container py-4 pb-5">
    @include('components.navigation.breadcrumb', ['items' => [
        ['label' => 'Home', 'url' => route('home')], ['label' => 'Learn'],
    ]])

    @component('components.layout.hub-header', ['heading' => 'PianoLIT Learn', 'preview' => false])
        Music theory tools, email courses, visual guides, quizzes, and pianist biographies.
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
