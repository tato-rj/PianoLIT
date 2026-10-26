<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="description" content="A simple metronome. Tap to play, swipe to find your tempo.">
    <title>Metronome | PianoLIT</title>
    <link rel="canonical" href="{{ route('metronome.home') }}">
    @include('layouts.html.google.fonts')
    @include('components.forms.number-fonts')
    @include('layouts.html.theme')
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <link href="{{ mix('css/metronome.css') }}" rel="stylesheet">
    <script src="{{ mix('js/metronome.js') }}" defer></script>
</head>
<body>
    @include('components.branding.watermark')
    <main>
        <button
            class="metronome"
            type="button"
            data-metronome
            aria-label="Start metronome. 120 beats per minute."
            aria-pressed="false"
            aria-describedby="metronome-instructions"
        >
            <span class="metronome__pulse" data-metronome-pulse aria-hidden="true"></span>
            {{-- JavaScript sets the restored tempo before the number's first paint. --}}
            <span class="metronome__tempo" data-tempo data-number-font="nunito" aria-hidden="true"></span>
            <span class="metronome__conductor" data-conductor aria-hidden="true">
                @for ($beat = 1; $beat <= 4; $beat++)
                    <span class="conductor-count" data-conductor-count>{{ $beat }}</span>
                @endfor
                <svg viewBox="0 0 400 600">
                    <path data-conductor-path class="conductor-path" fill="none"/>
                </svg>
                <span class="conductor-ball" data-conductor-ball>
                    <span class="conductor-halo"></span>
                    <span class="conductor-dot"></span>
                    @include('metronome.conductor-hand')
                </span>
            </span>
        </button>
        <button
            class="metronome__edge metronome__edge--left"
            type="button"
            data-tempo-step="-1"
            aria-label="Decrease tempo by 1. 120 beats per minute."
            aria-describedby="metronome-instructions"
        ></button>
        <button class="metronome__settings-button" type="button" data-settings-open
            aria-label="Settings" aria-haspopup="dialog" aria-controls="metronome-settings" aria-expanded="false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m9.5 3-.6 2.4-1.8 1-2.4-.6-2.5 4.4 1.8 1.7v2.2l-1.8 1.7 2.5 4.4 2.4-.6 1.8 1 .6 2.4h5l.6-2.4 1.8-1 2.4.6 2.5-4.4-1.8-1.7v-2.2l1.8-1.7-2.5-4.4-2.4.6-1.8-1L14.5 3z" transform="translate(1 0) scale(.92)"/>
                <circle cx="12" cy="12" r="3.3"/>
            </svg>
        </button>
        <button
            class="metronome__edge metronome__edge--right"
            type="button"
            data-tempo-step="1"
            aria-label="Increase tempo by 1. 120 beats per minute."
            aria-describedby="metronome-instructions"
        ></button>
        <p id="metronome-instructions" class="visually-hidden">
            Tap or click the center to start or stop. Tap the left edge to decrease the tempo
            by one, or the right edge to increase it by one. Hold either edge to keep
            adjusting. Swipe up in the center to increase the tempo,
            or down to decrease it. Swipe slowly for fine adjustments, quickly for
            bigger changes. Tempo ranges from 20 to 300 beats per minute.
            You can also use Space or Enter to start or stop, and the up and down
            arrow keys to adjust the tempo.
        </p>
        <p class="metronome__message" data-metronome-message role="status" hidden></p>
        <noscript><p class="metronome__message">Enable JavaScript to use the metronome.</p></noscript>
    </main>
    @include('metronome.settings')
    @include('components.tracking.activity')
</body>
</html>
