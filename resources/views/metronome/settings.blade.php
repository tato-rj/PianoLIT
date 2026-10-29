@component('components.dialogs.bottom-sheet.panel', ['id' => 'metronome-settings', 'titleId' => 'settings-title', 'classes' => 'metronome-settings', 'dragToClose' => true, 'closeLabel' => 'Close settings', 'data' => ['settings-panel' => '']])
    <div class="bottom-sheet__panel">
        @include('components.dialogs.bottom-sheet.header', ['titleId' => 'settings-title', 'title' => 'Settings', 'closeLabel' => 'Close settings', 'back' => true, 'dragToClose' => true])
        <div class="p-4 overflow-auto" data-settings-scroll>
            <div data-settings-page="home" class="d-grid gap-3">
                @include('components.navigation.settings-link', ['target' => 'rhythm', 'icon' => 'activity', 'label' => 'Rhythm', 'summary' => '1 beat · Pulse'])
                @include('components.navigation.settings-link', ['target' => 'sound', 'icon' => 'volume-2', 'label' => 'Sound', 'summary' => 'Click'])
                @include('components.navigation.settings-link', ['target' => 'appearance', 'icon' => 'palette', 'label' => 'Appearance', 'summary' => 'Color & numbers'])
            </div>
            <div data-settings-page="sound" hidden>
                @php($hideSoundLabel = true)
                @auth('web')
                    @include('metronome.components.voice-recorder')
                @else
                    @include('metronome.components.sound-menu')
                @endauth
                <div class="mt-4" data-effect-entry hidden>
                    @include('components.navigation.settings-link', ['target' => 'effects', 'icon' => 'sparkles', 'label' => 'Voice effect', 'summary' => 'Normal'])
                </div>
                <div class="mt-3" data-spoken-feedback hidden>
                    @include('components.feedback.alert', ['color' => 'warning', 'fullX' => true, 'message' => '<span data-spoken-error></span>'])
                </div>
                <p class="text-muted mb-0 mt-3" data-spoken-loading role="status" hidden>Loading voice…</p>
            </div>
            <div data-settings-page="effects" hidden>
                @include('components.forms.choice-buttons', ['name' => 'voice-effect', 'label' => 'Voice effect', 'value' => 'classic', 'options' => json_decode(file_get_contents(resource_path('data/metronome-voice-effects.json')), true)])
                <p class="text-muted mt-3 mb-0" data-effect-loading role="status" hidden>Preparing effect…</p>
                <div class="mt-3" data-effect-feedback hidden>
                    @include('components.feedback.alert', ['color' => 'warning', 'fullX' => true, 'message' => '<span data-effect-error></span>'])
                </div>
            </div>
            <div data-settings-page="rhythm" hidden>
                <div class="d-grid gap-4">
                    <fieldset>
                        <legend class="h6 mb-2">Beats per bar</legend>
                        @include('components.forms.segmented-control', ['id' => 'metronome-beats', 'name' => 'metronome-beats', 'label' => 'Beats per bar', 'options' => [1 => '1', 2 => '2', 3 => '3', 4 => '4'], 'value' => 1])
                    </fieldset>
                    <fieldset>
                        <legend class="h6 mb-2">Motion</legend>
                        @include('components.forms.segmented-control', ['id' => 'metronome-type', 'name' => 'metronome-type', 'label' => 'Motion', 'options' => ['classic' => 'Pulse', 'conductor' => 'Conductor'], 'value' => 'classic'])
                    </fieldset>
                    <div data-conductor-options hidden>
                        <fieldset class="mb-4">
                            <legend class="h6 mb-2">Indicator</legend>
                            @include('components.forms.segmented-control', ['id' => 'metronome-marker', 'name' => 'metronome-marker', 'label' => 'Indicator', 'options' => ['dot' => 'Dot', 'hand' => 'Hand'], 'value' => 'dot'])
                        </fieldset>
                        <div class="d-flex align-items-center justify-content-between gap-3">
                            <span>Show path</span>
                            @toggle(['toggle' => false, 'name' => 'metronome-path', 'autoToggle' => false, 'ariaLabel' => 'Show conductor path'])
                        </div>
                    </div>
                </div>
            </div>
            <div data-settings-page="appearance" hidden>
                <div class="d-grid gap-4">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <div>
                            <label class="h6 mb-1" for="metronome-color">Background</label>
                            <span class="visually-hidden" data-color-value>#0055FE</span>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <button class="btn btn-secondary btn-sm" type="button" data-settings-color-reset aria-label="Reset color to PianoLIT blue">Reset</button>
                            @include('components.forms.color', ['id' => 'metronome-color', 'name' => 'metronome-color', 'value' => '#0055fe', 'dataAttribute' => 'settings-color'])
                        </div>
                    </div>
                    @include('components.forms.font-select', ['id' => 'metronome-font', 'previewSource' => '[data-tempo]'])
                </div>
            </div>
            <button class="btn btn-primary w-100 mt-4" type="button" data-bs-dismiss="offcanvas" data-settings-close data-settings-field>Done</button>
        </div>
    </div>
@endcomponent
