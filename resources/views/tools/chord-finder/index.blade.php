@php
    $searchTitle = 'Piano Chord Finder';
    $searchDescription = 'Identify a piano chord from its notes. Use the keyboard or enter note names to see possible chords, hear the voicing, and understand the intervals.';
    $canonical = route('tools.chord-finder.index');
    $breadcrumbs = [
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Learn', 'url' => route('learn.home')],
        ['label' => 'Chord Finder'],
    ];
    $searchEntity = ['@type' => 'WebApplication', 'name' => $searchTitle, 'url' => $canonical, 'description' => $searchDescription, 'isAccessibleForFree' => true];
@endphp
@extends('layouts.app', ['title' => $searchTitle.' | PianoLIT Learn', 'bodyClass' => 'learn-home chord-finder'])

@section('page-metadata')
    @include('components.seo.resource-head', ['searchImage' => asset('images/misc/thumbnails/chords.jpg')])
@endsection

@section('content')
<div class="container py-4 pb-5">
    @include('components.navigation.breadcrumb', ['items' => $breadcrumbs])
    <header class="learn-home__intro mb-5">
        <h1 class="mb-3">Chord Finder</h1>
        <p class="text-muted mb-0">Select at least three notes to find a chord.</p>
    </header>

    @component('components.layout.editorial')
        <div id="notes-container" data-url="{{ route('tools.chord-finder.analyse') }}">
            @include('tools.chord-finder.empty')
        </div>
        <section class="border-top pt-4 mt-5" aria-labelledby="chord-guide-title">
            <h2 class="h4 mb-3" id="chord-guide-title">How to find a piano chord from its notes</h2>
            <p>Choose at least three different notes on the keyboard or enter their names. Select <strong>Find chords</strong> to see possible names. Choose a result to hear the chord and see the notes on the keyboard.</p>
            <p>For example, C, E, and G form C major. When E is the lowest note, the chord is in first inversion and can be written C/E. When the notes fit more than one chord, you can specify a root or key signature to help narrow the results.</p>
            <p class="mb-0">Use the <a href="{{ route('tools.circle-of-fifths') }}">interactive circle of fifths</a> to compare key signatures and see how chords relate to a key.</p>
        </section>
        @slot('sidebar')
            @include('components.promotions.ads', ['ads' => ['crashcourse', 'ebook', 'escore'], 'variant' => 'cards'])
        @endslot
    @endcomponent
</div>
@include('tools.chord-finder.key-signature-help')
@component('components.dialogs.modal', ['id' => 'chord-results-modal', 'header' => 'Chord results', 'centered' => true, 'options' => ['size' => 'lg']])
    @slot('body')
        <div id="chord-results" aria-busy="false"></div>
    @endslot
    @slot('footer')
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">@icon('arrow-left')Edit notes</button>
    @endslot
@endcomponent
<template id="chord-spelling-template">
    <div class="chord-finder__spelling">@select(['bag' => 'default', 'id' => 'chord-spelling', 'name' => 'spelling', 'label' => 'First note', 'choices' => [], 'required' => false, 'classes' => 'form-control-sm', 'grid' => 'mb-0'])</div>
</template>
<template id="chord-note-pill">@pill(['label' => '<span data-note-label></span>', 'theme' => 'grey'])</template>
@endsection

@push('scripts')
@addthis
<script src="{{ mix('js/tone.js') }}"></script>
<script src="{{ asset('js/components/piano.js') }}"></script>
<script src="{{ mix('js/components/chord-finder.js') }}"></script>
@endpush
