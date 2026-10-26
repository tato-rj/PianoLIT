@extends('layouts.app', [
	'title' => 'Circle of Fifths | PianoLIT Learn',
    'bodyClass' => 'learn-home circle-tool',
	'shareable' => [
		'keywords' => 'circle of fifths,music theory,circle,fifths,music theory,chords',
		'title' => 'The Interactive Circle of Fifths',
		'description' => 'An interactive Circle of Fifths that will help you understand what it is and how to use it.',
		'thumbnail' => asset('images/misc/thumbnails/circle.jpg'),
		'created_at' => carbon('20-06-2019'),
		'updated_at' => carbon('20-06-2019')
	]])

@push('header')
<link href="https://fonts.googleapis.com/css?family=Roboto+Slab:700&display=swap" rel="stylesheet">
@endpush

@section('content')

<div class="container py-4 pb-5">
    @include('components.navigation.breadcrumb', ['items' => [
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Learn', 'url' => route('learn.home')],
        ['label' => 'Circle of fifths'],
    ]])

    <header class="learn-home__intro mb-5">
        <h1 class="mb-3">Circle of Fifths</h1>
        <p class="text-muted mb-0">Find the connections between keys, chords, and scales. <br class="d-none d-md-block">Turn the wheel, compare modes, and hear the notes on the piano.</p>
    </header>

    @component('components.layout.editorial')
        <section id="circle-workspace" aria-labelledby="circle-workspace-title">
            <div class="mb-4">
                <h2 id="circle-workspace-title" class="h5 mb-2">Explore a key</h2>
                <p class="small text-muted mb-0">Choose a key on the circle or use the arrows to move around it.</p>
            </div>
            <div class="row gy-4 gx-xl-4">
                <div class="col-xl-5">
                    <div id="wheel-container" class="w-100 position-relative">
                        @include('tools.circle-of-fifths.wheel')
                        <div class="circle-tool__tonic" aria-hidden="true"><span data-circle-tonic>C</span><small data-circle-mode>major</small></div>
                    </div>
                    <div id="wheel-controls" class="d-flex flex-wrap justify-content-center align-items-center gap-2 mt-3">
                        <button type="button" direction="right" class="btn btn-secondary btn-sm" aria-label="Previous key" title="Previous key">@icon('arrow-left', ['mr' => 0])</button>
                        <button type="button" id="circle-reset" class="btn btn-secondary btn-sm">Reset to C</button>
                        <button type="button" direction="left" class="btn btn-secondary btn-sm" aria-label="Next key" title="Next key">@icon('arrow-right', ['mr' => 0])</button>
                    </div>
                    <p class="small text-muted text-center mt-3 mb-0">Use ← and → when the wheel has focus.</p>
                </div>
                <div class="col-xl-7" id="labels-container">
                    <div id="mode-controls">
                        @component('components.navigation.tab-navigation', [
                            'id' => 'mode-tabs', 'label' => 'Key mode', 'variant' => 'segmented', 'class' => 'mode-tabs mb-4',
                            'items' => [
                                ['id' => 'major-tab', 'target' => 'mode-major', 'label' => 'Major', 'data' => ['name' => 'major']],
                                ['id' => 'minor-tab', 'target' => 'mode-minor', 'label' => 'Minor', 'data' => ['name' => 'minor']],
                                ['id' => 'enharmonic-tab', 'target' => 'mode-enharmonic', 'label' => 'Enharmonic', 'data' => ['name' => 'enharmonic']],
                            ],
                        ])
                            <div class="tab-content t-2" id="mode-panels" style="opacity: 0;">
                                @include('tools.circle-of-fifths.labels.major')
                                @include('tools.circle-of-fifths.labels.minor')
                                @include('tools.circle-of-fifths.labels.enharmonic')
                            </div>
                        @endcomponent
                    </div>
                </div>
            </div>
        </section>

        <section id="circle-piano" class="mt-5" aria-labelledby="circle-piano-title">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                <div>
                    <h2 id="circle-piano-title" class="h5 mb-2">Hear the scale</h2>
                    <p class="small text-muted mb-0">The highlighted notes belong to your selected scale. Tap any piano key to hear it.</p>
                </div>
                <button type="button" id="circle-play-scale" class="btn btn-primary btn-sm" aria-pressed="false">
                    <span data-play-icon>@icon('play')</span><span data-stop-icon class="d-none">@icon('square')</span><span data-play-label>Play scale</span>
                </button>
            </div>
            @include('components.music.piano.keyboard', [
                'centered' => true, 'responsive' => true,
                'octaves' => [
                    3 => [[true, false], [true, false], [true, false], [true, false], [true, false], [true, false], [true, false]],
                    4 => [],
                ],
            ])
            <p id="circle-audio-status" class="small text-muted mt-3 mb-0 d-none" role="status"></p>
        </section>

        <div class="mt-5">
            @include('components.newsletter.invitation', ['headingId' => 'circle-newsletter-title', 'preview' => false])
        </div>

        @slot('sidebar')
            <div class="mb-4">
                <h2 class="h5 mb-2">Explore PianoLIT</h2>
                <p class="text-muted small mb-0">Keep your curiosity playing.</p>
            </div>
            @include('components.promotions.ads', ['ads' => ['crashcourse', 'ebook', 'escore'], 'variant' => 'cards'])
        @endslot
    @endcomponent
</div>

<template id="circle-key-pill">@pill(['label' => '<span class="key-label"></span>', 'theme' => 'grey'])</template>
<template id="circle-chord-pill">@pill(['label' => '<span class="roman-numeral"></span><span class="chord-name"></span>', 'theme' => 'grey', 'classes' => 'd-inline-flex align-items-center gap-2'])</template>

@include('tools.circle-of-fifths.info.key')
@include('tools.circle-of-fifths.info.signature')
@include('tools.circle-of-fifths.info.relative')
@include('tools.circle-of-fifths.info.neighbors')
@include('tools.circle-of-fifths.info.functional')

@endsection

@push('scripts')
@addthis
<script type="text/javascript" src="{{mix('js/tone.js')}}"></script>
<script type="text/javascript" src="{{mix('js/components/circle-of-fifths.js')}}"></script>
<script type="text/javascript" src="{{asset('js/components/piano.js')}}"></script>
@endpush
