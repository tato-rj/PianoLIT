<section id="chord-editor" aria-labelledby="chord-editor-title">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <h2 id="chord-editor-title" class="h5 mb-0">Choose your notes</h2>
        <button type="button" class="btn btn-secondary btn-sm" id="chord-clear">@icon('rotate-ccw')Clear notes</button>
    </div>
    @component('components.navigation.tab-navigation', [
        'id' => 'chord-input-tabs', 'label' => 'Choose how to enter notes', 'variant' => 'segmented', 'class' => 'mb-4',
        'items' => [['target' => 'chord-keyboard', 'label' => 'Piano keyboard'], ['target' => 'chord-note-names', 'label' => 'Note names']],
    ])
        <div class="mb-4">
            <div class="d-flex flex-wrap align-items-baseline gap-1">
                @label(['id' => 'chord-key-signature', 'label' => 'Key signature'])
                <a class="small" href="#chord-key-signature-help" data-bs-toggle="modal" role="button" aria-haspopup="dialog" aria-controls="chord-key-signature-help">(why does it matter?)</a>
            </div>
            <div class="d-flex">
                @select(['bag' => 'default', 'id' => 'chord-key-signature', 'name' => 'key_signature', 'label' => null, 'choices' => \App\Resources\ChordFinder\KeySignature::choices(), 'required' => false, 'classes' => 'form-control-sm', 'grid' => 'mb-0'])
            </div>
        </div>
        <div class="tab-content">
            <div class="tab-pane fade show active" id="chord-keyboard" role="tabpanel" aria-labelledby="chord-input-tabs-chord-keyboard">
                <div class="keyboard-input" id="chord-input-piano">
                    @include('components.music.piano.keyboard', ['centered' => true, 'responsive' => true, 'octaves' => [3 => [], 4 => []]])
                </div>
            </div>
            <div class="tab-pane fade" id="chord-note-names" role="tabpanel" aria-labelledby="chord-input-tabs-chord-note-names">
                <div class="chord-finder__notes">
                    @foreach(['C' => 4, 'D' => 4, 'E' => 4, 'F' => 4, 'G' => 4, 'A' => 3, 'B' => 3] as $note => $octave)
                        @include('tools.chord-finder.inputs.chord-note', ['note' => $note, 'octave' => $octave])
                    @endforeach
                </div>
            </div>
        </div>
    @endcomponent
    <div class="d-flex flex-wrap justify-content-center align-items-center gap-2 mt-4" aria-live="polite">
        <span class="small text-muted" id="chord-selection-count">No notes selected</span><div class="d-flex flex-wrap gap-2" id="chord-selected-notes"></div>
    </div>
    <div id="chord-error" class="mt-4" hidden>
        @alert(['color' => 'red', 'message' => '<span data-chord-error-message></span>'])
    </div>
    <div class="d-flex justify-content-center mt-4">
        <button type="button" class="btn btn-primary" id="submit-notes" aria-haspopup="dialog" aria-controls="chord-results-modal">Find chords @icon('arrow-right', ['mr' => 0, 'ml' => 2])</button>
    </div>
    <div id="chord-options" class="mt-4" hidden>
        <h3 id="chord-options-title" class="h6 mb-3">Note spelling &amp; root</h3>
        <div class="d-flex flex-wrap align-items-end gap-3">
            <div id="options-container" hidden>
                <div id="options-buttons" class="d-flex flex-wrap gap-2"></div>
            </div>
            <div id="root-container" class="chord-finder__root" hidden>
                @select(['bag' => 'default', 'id' => 'chord-root', 'name' => 'root', 'label' => 'Root', 'choices' => ['' => 'Auto detect'], 'required' => false, 'classes' => 'form-control-sm', 'grid' => 'mb-0'])
            </div>
        </div>
    </div>
    <p id="chord-audio-status" class="small text-muted mt-3 mb-0" role="status" hidden></p>
    <noscript><p class="mt-3">Please enable JavaScript to select notes and find chords.</p></noscript>
</section>
