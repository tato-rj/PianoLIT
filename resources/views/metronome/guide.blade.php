@php
    $searchTitle = 'How to Use the Online Metronome';
    $searchDescription = 'Learn how to start the PianoLIT metronome, set beats per minute, choose beats per bar, and use a visual pulse or conductor.';
    $canonical = route('metronome.guide');
    $breadcrumbs = [
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Metronome', 'url' => route('metronome.home')],
        ['label' => 'How to use'],
    ];
@endphp
@extends('layouts.app', ['title' => $searchTitle.' | PianoLIT'])

@section('page-metadata')
    @include('components.seo.resource-head', ['siteName' => 'PianoLIT', 'searchImage' => asset('images/home/worlds/metronome.webp')])
@endsection

@section('content')
<div class="container py-4 pb-5">
    @include('components.navigation.breadcrumb', ['items' => $breadcrumbs])
    @component('components.layout.editorial', ['showSidebar' => false])
        <article class="col-lg-8">
            <header class="mb-4">
                <h1 class="h2 mb-3">{{ $searchTitle }}</h1>
                <p>A metronome plays a steady beat. Tempo is measured in beats per minute (BPM). At 60 BPM, you hear one beat per second. At 120 BPM, you hear two.</p>
                <a class="btn btn-primary" href="{{ route('metronome.home') }}">Open the metronome</a>
            </header>
            <section class="border-top pt-4 mb-4" aria-labelledby="metronome-tempo-title">
                <h2 class="h4" id="metronome-tempo-title">Start playback and set the tempo</h2>
                <p>Tap or click the center to start or stop. Tap the left edge to slow down by 1 BPM or the right edge to speed up by 1 BPM. Hold either edge to keep adjusting, or swipe up or down in the center. The tempo ranges from 20 to 300 BPM.</p>
                <p class="mb-0">On a keyboard, press Space or Enter to start or stop. Use the Up and Down arrows to change the tempo.</p>
            </section>
            <section class="border-top pt-4 mb-4" aria-labelledby="metronome-beats-title">
                <h2 class="h4" id="metronome-beats-title">Set the beats per bar</h2>
                <p class="mb-0">In Settings, choose one to four beats per bar. Try three beats per bar for music in 3/4 or four for 4/4. You can also choose a sound or switch from the pulse display to the conductor.</p>
            </section>
            <section class="border-top pt-4" aria-labelledby="metronome-practice-title">
                <h2 class="h4" id="metronome-practice-title">Practice a short passage</h2>
                <p class="mb-0">Choose a tempo slow enough to play a few measures evenly. Count a bar before playing. When you can repeat the passage with a steady beat, raise the tempo a little and try again.</p>
            </section>
        </article>
    @endcomponent
</div>
@endsection
