<section class="voice-recorder mt-3" @if($preview ?? false) data-ds-preview @else data-voice-recorder data-url="{{ route('metronome.voice.show') }}" data-csrf="{{ csrf_token() }}" data-owner="{{ auth('web')->id() }}" @endif aria-label="Your counting voice">
    <div data-voice-summary>
        <p class="small text-muted mb-2" data-voice-summary-text>Count in your own voice.</p>
        <div data-voice-library @unless($preview ?? false) hidden @endunless>
            @select(['bag' => 'default', 'id' => 'metronome-voice-choice', 'name' => 'metronome_voice_choice', 'label' => 'Saved voices', 'grid' => 'mb-2', 'choices' => ($preview ?? false) ? ['sample' => 'My voice', 'soft' => 'Soft counting'] : [], 'value' => 'sample', 'required' => false])
            <div class="d-flex flex-wrap gap-2 mb-2">
                <button type="button" class="btn btn-secondary" data-voice-listen>@icon('play')Listen</button>
                <button type="button" class="btn btn-secondary" data-voice-rename>Rename</button>
                <button type="button" class="btn btn-secondary" data-voice-delete>Delete</button>
            </div>
            <div data-voice-rename-panel hidden>
                @input(['bag' => 'default', 'id' => 'metronome-voice-rename', 'name' => 'metronome_voice_rename', 'label' => 'Recording name', 'grid' => 'mb-2', 'limit' => 60, 'required' => false, 'autocomplete' => 'off'])
                <div class="d-flex gap-2 mb-3">
                    <button type="button" class="btn btn-secondary" data-voice-rename-cancel>Cancel</button>
                    <button type="button" class="btn btn-primary" data-voice-rename-save>Save name</button>
                </div>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-secondary" data-voice-start @unless($preview ?? false) disabled @endunless>@icon('mic')<span data-voice-start-label>Record my voice</span></button>
        </div>
    </div>
    <div data-voice-panel hidden>
        <p class="small text-muted text-center mb-2" data-voice-step role="status" aria-live="polite"></p>
        @include('components.voice-recorder-cue')
        <div class="progress mb-4" data-voice-meter role="progressbar" aria-label="Microphone level" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
            <div class="progress-bar bg-success" data-voice-level></div>
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
            @input(['bag' => 'default', 'id' => 'metronome-voice-name', 'name' => 'metronome_voice_name', 'label' => 'Recording name', 'placeholder' => 'e.g. Calm counting', 'limit' => 60, 'grid' => 'mb-3', 'required' => false, 'autocomplete' => 'off'])
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary flex-fill" data-voice-cancel>Cancel</button>
            <button type="button" class="btn btn-primary flex-fill" data-voice-retry hidden>Try again</button>
            <button type="button" class="btn btn-primary flex-fill" data-voice-save hidden>Save and use</button>
        </div>
    </div>
    <div class="mt-3" data-voice-delete-confirm hidden>
        <p class="mb-2" data-voice-delete-label>Delete this recording?</p>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-secondary" data-voice-keep>Keep it</button>
            <button type="button" class="btn btn-danger" data-voice-confirm-delete>Delete recording</button>
        </div>
    </div>
    <p class="small mt-3 mb-0" data-voice-status role="status" aria-live="polite"></p>
    <button type="button" class="btn btn-secondary mt-2" data-voice-reload hidden>Reload recording</button>
    <a href="{{ route('metronome.voice.start') }}" class="btn btn-secondary mt-2" data-voice-signin hidden>Sign in</a>
</section>
