@extends('layouts.app', [
    'title' => 'Chord Finder | PianoLIT Learn',
    'bodyClass' => 'learn-home chord-finder',
    'shareable' => [
        'keywords' => 'chords,chord finder,music theory,harmony',
        'title' => 'The Ultimate Chord Finder',
        'description' => 'Find possible chords from your notes, hear them on the piano, and understand the intervals behind each name.',
        'thumbnail' => asset('images/misc/thumbnails/chords.jpg'),
        'created_at' => carbon('10-08-2019'),
        'updated_at' => carbon('20-08-2019')
    ]])

@section('content')
<div class="container py-4 pb-5">
    @include('components.breadcrumb', ['items' => [
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Learn', 'url' => route('learn.home')],
        ['label' => 'Chord finder'],
    ]])
    <header class="learn-home__intro mb-5">
        <h1 class="mb-3">Chord Finder</h1>
        <p class="text-muted mb-0">A few notes, a world of possibilities. <br class="d-none d-md-block">Find your chord, hear how it sounds, and discover how it gets its name.</p>
    </header>

    @component('components.editorial-layout')
        <div id="notes-container" data-url="{{ route('tools.chord-finder.analyse') }}">
            @include('tools.chord-finder.empty')
            <div id="chord-results" class="mt-5" aria-live="polite" aria-busy="false"></div>
        </div>
        <section class="mt-5 border-top pt-4" aria-labelledby="chord-guide-title">
            <h2 id="chord-guide-title" class="h5 mb-3">A little help finding your chord</h2>
            <p class="text-muted">Choose at least three different notes. A piano key can have more than one name: C♯ and D♭ sound the same, but their spelling changes how a chord is named. Use the note-name options if you want a particular spelling.</p>
            <p class="text-muted mb-0">Several chords can share the same notes. We’ll show the most likely matches first, followed by other possibilities. Select any result to hear it and explore its intervals.</p>
        </section>
        <div class="mt-5">@include('components.newsletter.invitation', ['headingId' => 'chord-newsletter-title', 'preview' => false])</div>
        @slot('sidebar')
            <div class="mb-4"><h2 class="h5 mb-2">Explore PianoLIT</h2><p class="text-muted small mb-0">Keep your curiosity playing.</p></div>
            @include('components.display.ads', ['ads' => ['crashcourse', 'ebook', 'escore'], 'variant' => 'cards'])
        @endslot
    @endcomponent
</div>
<template id="chord-spelling-template">
    <div class="col-6 col-sm-4 col-xl-3">@select(['bag' => 'default', 'id' => 'chord-spelling', 'name' => 'spelling', 'label' => 'Note name', 'choices' => [], 'required' => false])</div>
</template>
<template id="chord-note-pill">@pill(['label' => '<span data-note-label></span>', 'theme' => 'grey'])</template>
@endsection

@push('scripts')
@addthis
<script src="{{ mix('js/tone.js') }}"></script>
<script src="{{ asset('js/components/piano.js') }}"></script>
<script src="{{ mix('js/components/chord-finder.js') }}"></script>
@endpush
