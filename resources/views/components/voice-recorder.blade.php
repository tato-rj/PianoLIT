<section class="voice-recorder mt-3" @if($preview ?? false) data-ds-preview @else data-voice-recorder data-url="{{ route('metronome.voice.show') }}" data-csrf="{{ csrf_token() }}" data-owner="{{ auth('web')->id() }}" @endif aria-label="Your counting voice">
    <div data-voice-summary>
        <p class="small text-muted mb-2" data-voice-summary-text>Count in your own voice.</p>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-secondary" data-voice-start @unless($preview ?? false) disabled @endunless>@icon('mic')<span data-voice-start-label>Record my voice</span></button>
            <button type="button" class="btn btn-secondary" data-voice-listen hidden>@icon('play')Listen</button>
            <button type="button" class="btn btn-secondary" data-voice-delete hidden>Delete</button>
        </div>
    </div>
    <div data-voice-panel hidden>
        <p class="small text-muted text-center mb-2" data-voice-step role="status" aria-live="polite"></p>
        <div class="voice-recorder__number text-center" data-voice-number aria-hidden="true">1</div>
        <h2 class="h4 text-center mb-3" data-voice-prompt aria-live="polite" aria-atomic="true">Say “one”</h2>
        <div class="progress mb-4" data-voice-meter role="progressbar" aria-label="Microphone level" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
            <div class="progress-bar bg-danger" data-voice-level></div>
        </div>
        <div data-voice-review hidden>
            <div class="row g-2 mb-3">
                @foreach([1, 2, 3, 4] as $beat)
                    <div class="col-3"><button type="button" class="btn btn-secondary w-100" data-voice-preview="{{ $beat - 1 }}" aria-label="Listen to {{ $beat }}">@icon('play', ['mr' => 0]) {{ $beat }}</button></div>
                @endforeach
            </div>
            <div class="d-flex flex-wrap justify-content-center gap-2 mb-4">
                <button type="button" class="btn btn-secondary" data-voice-preview-all>@icon('play')Listen to all</button>
                <button type="button" class="btn btn-secondary" data-voice-redo>Redo 1</button>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary flex-fill" data-voice-cancel>Cancel</button>
            <button type="button" class="btn btn-primary flex-fill" data-voice-retry hidden>Try again</button>
            <button type="button" class="btn btn-primary flex-fill" data-voice-save hidden>Use my voice</button>
        </div>
    </div>
    <div class="mt-3" data-voice-delete-confirm hidden>
        <p class="mb-2">Delete your recording?</p>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary" data-voice-keep>Keep it</button>
            <button type="button" class="btn btn-danger" data-voice-confirm-delete>Delete recording</button>
        </div>
    </div>
    <p class="small mt-3 mb-0" data-voice-status role="status" aria-live="polite"></p>
    <button type="button" class="btn btn-secondary mt-2" data-voice-reload hidden>Reload recording</button>
    <a href="{{ route('metronome.voice.start') }}" class="btn btn-secondary mt-2" data-voice-signin hidden>Sign in</a>
</section>
