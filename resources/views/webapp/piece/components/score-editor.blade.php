<div id="score-editor" class="score-editor mb-4" data-pdf-url="{{ storage($piece->score_path) }}"
    data-score-version="{{ hash('sha256', $piece->score_path) }}"
    data-annotations-url="{{ route('webapp.pieces.score.annotations.show', $piece) }}">
    <div class="">

        <div class="score-toolbar mt-2 d-apart" role="toolbar" aria-label="Score annotation tools">
            <div class="btn-group" role="group" aria-label="Tool">
                {{-- <button type="button" data-tool="read" data-edit-control class="btn btn-sm btn-light active" aria-pressed="true" disabled>Read</button> --}}
                <button type="button" data-tool="pen" data-edit-control class="btn border-0 btn-sm btn-light mr-2" aria-pressed="false" disabled>@fa(['icon' => 'pen', 'mr' => 0])</button>
                <button type="button" data-tool="text" data-edit-control class="btn border-0 btn-sm btn-light mr-2" aria-pressed="false" disabled>@fa(['icon' => 'i-cursor', 'mr' => 0])</button>
                <button type="button" data-tool="erase" data-edit-control class="btn btn-sm btn-light border-0 mr-2" aria-pressed="false" disabled>@fa(['icon' => 'eraser', 'mr' => 0])</button>

                <label class="score-color-button btn btn-light mb-0 text-dark" title="Annotation color">
                    @fa(['icon' => 'palette', 'mr' => 0])
                    <input data-color data-edit-control type="color" value="#20252b" class="score-color-input" aria-label="Annotation color" disabled>
                </label>
{{--                 <label class="small mb-0">Pen width
                    <select data-width data-edit-control class="form-control form-control-sm" disabled>
                        <option value="0.002">Fine</option><option value="0.004" selected>Medium</option><option value="0.008">Bold</option>
                    </select>
                </label> --}}
            </div>
            <div class="btn-group" role="group" aria-label="Undo or redo markings">
                <button type="button" data-undo class="btn btn-sm border-0 btn-light mr-2" disabled>@fa(['icon' => 'undo', 'mr' => 0])</button>
                <button type="button" data-redo class="btn btn-sm border-0 btn-light" disabled>@fa(['icon' => 'redo', 'mr' => 0])</button>

                @if($piece->hasAudio())
                    <button id="launch-audio" data-url="{{route('webapp.pieces.audio', $piece)}}" class="btn btn-light ml-2">
                            @fa(['icon' => 'microphone', 'size' => '1x'])Listen</button>
                @endif
            </div>
        </div>
        <div data-text-options class="mt-3" hidden>
            <label class="small mb-0">Text size
                <select data-text-size data-edit-control class="form-control form-control-sm" disabled>
                    <option value="0.02">Small</option><option value="0.03" selected>Medium</option><option value="0.045">Large</option>
                </select>
            </label>
            <p class="small text-muted mt-1 mb-0">Click the score and type. Click existing text to edit it.</p>
        </div>
    </div>
    <div class="score-navigation py-2">
        <div class="d-flex align-items-center">
            <button type="button" data-prev class="btn btn-sm btn-light" aria-label="Previous score page" disabled>&larr;</button>
            <span data-page-label class="small mx-2" aria-live="polite">Page &mdash;</span>
            <button type="button" data-next class="btn btn-sm btn-light" aria-label="Next score page" disabled>&rarr;</button>
        </div>
        <div class="d-flex align-items-center">
            <button type="button" data-zoom="-0.25" class="btn btn-sm btn-light" aria-label="Zoom out" disabled>&minus;</button>
            <span data-zoom-label class="small mx-2">100%</span>
            <button type="button" data-zoom="0.25" class="btn btn-sm btn-light" aria-label="Zoom in" disabled>+</button>
        </div>
    </div>
    <div class="py-2">
        <div data-score-status class="small text-muted" role="status" aria-live="polite">Loading score…</div>
        <button type="button" data-retry-load class="btn btn-sm btn-light mt-2" hidden>Retry loading</button>
        <button type="button" data-retry-save class="btn btn-sm btn-light mt-2" hidden>Retry saving</button>
        <button type="button" data-reload-score class="btn btn-sm btn-light mt-2" hidden>Reload saved score</button>
    </div>
    <div class="score-scroll p-2">
        <div class="score-sheet">
            <canvas id="score-pdf" aria-label="Score PDF page"></canvas>
            <svg class="score-markings" aria-label="Draw or place markings on this score" role="img"></svg>
        </div>
    </div>
    <p class="small text-muted px-3 py-2 mb-0 border-top">The download contains the original score. Your markings stay in your account.</p>
</div>

<div class="text-center">
    <a href="{{ storage($piece->score_path) }}" target="_blank" rel="noopener" class="btn btn-outline-secondary">Download score</a>
</div>
