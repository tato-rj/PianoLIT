@component('components.bottom-sheet', ['id' => 'metronome-settings', 'titleId' => 'settings-title', 'classes' => 'metronome-settings', 'data' => ['settings-panel' => '']])
    <div class="bottom-sheet__panel">
        @include('components.bottom-sheet-header', ['titleId' => 'settings-title', 'title' => 'Settings', 'closeLabel' => 'Close settings'])
        <div class="p-4 overflow-auto" data-settings-scroll>
            <div class="d-grid gap-4">
                <fieldset data-settings-field>
                    <legend class="h6 mb-2">Beats</legend>
                    @include('components.segmented-control', ['id' => 'metronome-beats', 'name' => 'metronome-beats', 'label' => 'Beats', 'options' => [1 => '1', 2 => '2', 3 => '3', 4 => '4'], 'value' => 1])
                </fieldset>
                <div class="d-flex align-items-center justify-content-between gap-3" data-settings-field>
                    <div>
                        <label class="h6 mb-1" for="metronome-color">Color</label>
                        <p class="small text-muted mb-0" data-color-value>#0055FE</p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <button class="btn btn-secondary" type="button" data-settings-color-reset aria-label="Reset color to PianoLIT blue">Reset</button>
                        @include('components.form.color', ['id' => 'metronome-color', 'name' => 'metronome-color', 'value' => '#0055fe', 'dataAttribute' => 'settings-color'])
                    </div>
                </div>
                <div>
                    @auth('web')
                        @include('components.voice-recorder')
                    @else
                        @include('components.metronome-sound')
                    @endauth
                </div>
                <fieldset data-settings-field>
                    <legend class="h6 mb-2">Type</legend>
                    @include('components.segmented-control', ['id' => 'metronome-type', 'name' => 'metronome-type', 'label' => 'Type', 'options' => ['classic' => 'Classic', 'conductor' => 'Conductor'], 'value' => 'classic'])
                </fieldset>
                <div data-conductor-options data-settings-field hidden>
                    <fieldset class="mb-4">
                        <legend class="h6 mb-2">Indicator</legend>
                        @include('components.segmented-control', ['id' => 'metronome-marker', 'name' => 'metronome-marker', 'label' => 'Indicator', 'options' => ['dot' => 'Dot', 'hand' => 'Hand'], 'value' => 'dot'])
                    </fieldset>
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <span>Show conductor path</span>
                        @toggle(['toggle' => false, 'name' => 'metronome-path', 'autoToggle' => false, 'ariaLabel' => 'Show conductor path'])
                    </div>
                </div>
            </div>
            <button class="btn btn-primary w-100 mt-4" type="button" data-bs-dismiss="offcanvas" data-settings-close data-settings-field>Done</button>
        </div>
    </div>
@endcomponent
