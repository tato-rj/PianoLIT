@extends('layouts.app', [
    'title' => 'Chord Finder | PianoLIT Learn',
    'bodyClass' => 'learn-home chord-finder',
    'shareable' => [
        'keywords' => 'chords,chord finder,music theory,harmony',
        'title' => 'Chord Finder',
        'description' => 'Find possible chords from your notes, hear them on the piano, and understand the intervals behind each name.',
        'thumbnail' => asset('images/misc/thumbnails/chords.jpg'),
        'created_at' => carbon('10-08-2019'),
        'updated_at' => carbon('20-08-2019')
    ]])

@section('content')
<div class="container py-4 pb-5">
    @include('components.navigation.breadcrumb', ['items' => [
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Learn', 'url' => route('learn.home')],
        ['label' => 'Chord finder'],
    ]])
    <header class="learn-home__intro mb-5">
        <h1 class="mb-3">Chord Finder</h1>
        <p class="text-muted mb-0">Select at least three notes to find a chord.</p>
    </header>

    @component('components.layout.editorial')
        <div id="notes-container" data-url="{{ route('tools.chord-finder.analyse') }}">
            @include('tools.chord-finder.empty')
        </div>
        @slot('sidebar')
            @include('components.promotions.ads', ['ads' => ['crashcourse', 'ebook', 'escore'], 'variant' => 'cards'])
        @endslot
    @endcomponent
</div>
@component('components.dialogs.modal', ['id' => 'chord-results-modal', 'header' => 'Chord results', 'centered' => true, 'scrollable' => true, 'options' => ['size' => 'lg', 'body' => ['padding' => 0]]])
    @slot('body')
        <div id="chord-results" class="px-3 pb-3" aria-busy="false"></div>
    @endslot
    @slot('footer')
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">@icon('arrow-left')Edit notes</button>
    @endslot
@endcomponent
<template id="chord-spelling-template">
    <div class="chord-finder__spelling">@select(['bag' => 'default', 'id' => 'chord-spelling', 'name' => 'spelling', 'label' => 'Note name', 'choices' => [], 'required' => false, 'classes' => 'form-control-sm', 'grid' => 'mb-0'])</div>
</template>
<template id="chord-note-pill">@pill(['label' => '<span data-note-label></span>', 'theme' => 'grey'])</template>
@endsection

@push('scripts')
@addthis
<script src="{{ mix('js/tone.js') }}"></script>
<script src="{{ asset('js/components/piano.js') }}"></script>
<script src="{{ mix('js/components/chord-finder.js') }}"></script>
@endpush
