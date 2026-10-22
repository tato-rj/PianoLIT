<section id="chord-editor" aria-labelledby="chord-editor-title">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div><h2 id="chord-editor-title" class="h5 mb-2">Choose your notes</h2><p class="small text-muted mb-0">Tap the piano keys or build your chord by note name.</p></div>
        <button type="button" class="btn btn-secondary btn-sm" id="chord-clear">@icon('rotate-ccw')Clear notes</button>
    </div>
    @component('components.tab-navigation', [
        'id' => 'chord-input-tabs', 'label' => 'Choose how to enter notes', 'variant' => 'segmented', 'class' => 'mb-4',
        'items' => [['target' => 'chord-keyboard', 'label' => 'Piano keyboard'], ['target' => 'chord-note-names', 'label' => 'Note names']],
    ])
        <div class="tab-content">
            <div class="tab-pane fade show active" id="chord-keyboard" role="tabpanel" aria-labelledby="chord-input-tabs-chord-keyboard">
                <div class="keyboard-input" id="chord-input-piano">
                    @include('components.piano.keyboard', ['centered' => true, 'responsive' => true, 'octaves' => [3 => [], 4 => []]])
                </div>
            </div>
            <div class="tab-pane fade" id="chord-note-names" role="tabpanel" aria-labelledby="chord-input-tabs-chord-note-names">
                <div class="chord-finder__notes">
                    @foreach(['C' => 4, 'D' => 4, 'E' => 4, 'F' => 4, 'G' => 4, 'A' => 3, 'B' => 3] as $note => $octave)
                        @include('tools.chord-finder.inputs.chord-note', ['note' => $note, 'octave' => $octave])
                    @endforeach
                </div>
                <p class="small text-muted mt-3 mb-0">Select a letter, then use ♭ and ♯ to lower or raise it. Double flats and sharps are supported.</p>
            </div>
        </div>
    @endcomponent
    <div class="d-flex flex-wrap align-items-center gap-2 mt-4" aria-live="polite">
        <span class="small text-muted" id="chord-selection-count">No notes selected</span><div class="d-flex flex-wrap gap-2" id="chord-selected-notes"></div>
    </div>
    <div id="chord-error" class="alert alert-danger mt-4 mb-0" role="alert" hidden></div>
    <div class="d-flex flex-wrap align-items-center gap-3 mt-4">
        <button type="button" class="btn btn-primary" id="submit-notes">Find chords @icon('arrow-right', ['mr' => 0, 'ml' => 2])</button>
        <button type="button" class="btn btn-secondary" id="chord-example">Try C major</button>
    </div>
    <div id="chord-options" class="mt-4" hidden>
        @component('components.accordion', ['id' => 'chord-options-accordion'])
            @component('components.accordion-item', ['id' => 'chord-options-panel', 'parentId' => 'chord-options-accordion', 'title' => 'Note spelling & root (optional)'])
                <div id="options-container" hidden>
                    <h3 class="h6 mb-2">Note names</h3>
                    <p class="small text-muted mb-3">Change a spelling if you need an enharmonic equivalent.</p>
                    <div id="options-buttons" class="row g-3"></div>
                </div>
                <div id="root-container" class="row mt-4" hidden>
                    <div class="col-sm-6">@select(['bag' => 'default', 'id' => 'chord-root', 'name' => 'root', 'label' => 'Root note (optional)', 'choices' => ['' => 'Auto detect'], 'required' => false])</div>
                    <p class="small text-muted mb-0 mt-2">Have a root in mind? Choose it here, or let us compare all possibilities.</p>
                </div>
            @endcomponent
        @endcomponent
    </div>
    <p id="chord-audio-status" class="small text-muted mt-3 mb-0" role="status" hidden></p>
    <noscript><p class="mt-3">Please enable JavaScript to select notes and find chords.</p></noscript>
</section>
