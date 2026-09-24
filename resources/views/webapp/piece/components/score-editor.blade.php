<div id="score-editor" class="score-editor mb-4" data-pdf-url="{{ storage($piece->score_path) }}"
    data-score-version="{{ hash('sha256', $piece->score_path) }}"
    data-annotations-url="{{ route('webapp.pieces.score.annotations.show', $piece) }}">
    <div class="score-toolbar" role="toolbar" aria-label="Score annotation tools">
        <div class="score-tools" role="group" aria-label="Drawing tools">
            <button type="button" data-tool="pen" data-edit-control class="score-icon-button" aria-label="Pen" title="Pen" aria-pressed="false" disabled>@fa(['icon' => 'pen', 'mr' => 0])</button>
            <button type="button" data-tool="text" data-edit-control class="score-icon-button" aria-label="Text" title="Text" aria-pressed="false" disabled>@fa(['icon' => 'i-cursor', 'mr' => 0])</button>
            <button type="button" data-tool="erase" data-edit-control class="score-icon-button" aria-label="Eraser" title="Eraser" aria-pressed="false" disabled>@fa(['icon' => 'eraser', 'mr' => 0])</button>
            <button type="button" data-tool="highlight" data-edit-control class="score-icon-button" aria-label="Highlighter" title="Highlighter" aria-pressed="false" disabled>@fa(['icon' => 'highlighter', 'mr' => 0])</button>
            <label class="score-color-button score-icon-button mb-0" title="Annotation color">
                @fa(['icon' => 'palette', 'mr' => 0])
                <input data-color data-edit-control type="color" value="#20252b" class="score-color-input" aria-label="Annotation color" disabled>
            </label>
        </div>
        <div class="score-history" role="group" aria-label="Marking history">
            <button type="button" data-undo class="score-icon-button" aria-label="Undo" title="Undo" disabled>@fa(['icon' => 'undo', 'mr' => 0])</button>
            <button type="button" data-redo class="score-icon-button" aria-label="Redo" title="Redo" disabled>@fa(['icon' => 'redo', 'mr' => 0])</button>
            <button type="button" data-clear-all data-edit-control class="score-clear-button" aria-label="Clear all markings" title="Clear all markings" disabled>@fa(['icon' => 'trash-alt', 'mr' => 0])<span>Clear all</span></button>
        </div>
    </div>
    <div class="score-status-row"><span data-score-status class="score-save-status" role="status" aria-live="polite">Loading score…</span></div>

    <div class="score-scroll">
        <div class="score-sheet">
            <canvas id="score-pdf" aria-label="Score PDF page"></canvas>
            <svg class="score-markings" aria-label="Draw or place markings on this score" role="img"></svg>
        </div>
    </div>

    <div class="score-footer" role="toolbar" aria-label="Score view controls">
        <div class="score-view-controls">
            <div class="score-control-group" role="group" aria-label="Score page">
                <button type="button" data-prev class="score-control-button" aria-label="Previous score page" disabled>@fa(['icon' => 'chevron-left', 'mr' => 0])</button>
                <span data-page-label class="score-page-label" aria-live="polite">Page &mdash;</span>
                <button type="button" data-next class="score-control-button" aria-label="Next score page" disabled>@fa(['icon' => 'chevron-right', 'mr' => 0])</button>
            </div>
            <div class="d-flex" style="gap: 8px">
                <div class="score-control-group" role="group" aria-label="Score zoom">
                    <button type="button" data-zoom="-0.25" class="score-control-button" aria-label="Zoom out" disabled>&minus;</button>
                    <span data-zoom-label class="score-zoom-label">75%</span>
                    <button type="button" data-zoom="0.25" class="score-control-button" aria-label="Zoom in" disabled>+</button>
                </div>

            <button type="button" data-fullscreen class="score-control-button score-fullscreen-button" aria-label="Full screen" title="Full screen">@fa(['icon' => 'expand', 'mr' => 0])</button>
            </div>
        </div>
        <div class="score-footer-actions">
            @if($piece->hasAudio())
                <button type="button" id="launch-audio" data-url="{{ route('webapp.pieces.audio', $piece) }}" class="score-listen-button">@fa(['icon' => 'play', 'mr' => 0])<span>Listen</span></button>
            @endif
            <a href="{{ storage($piece->score_path) }}" target="_blank" rel="noopener" class="score-action-button" aria-label="Download score" title="Download the original score without markings">@fa(['icon' => 'download', 'mr' => 0])<span>Download</span></a>
            <button type="button" data-print class="score-action-button" title="Print all score pages with markings">@fa(['icon' => 'print', 'mr' => 0])<span>Print</span></button>
        </div>
    </div>
    <div class="score-messages">
        <button type="button" data-retry-load class="btn btn-sm btn-light" hidden>Retry loading</button>
        <button type="button" data-retry-save class="btn btn-sm btn-light" hidden>Retry saving</button>
        <button type="button" data-reload-score class="btn btn-sm btn-light" hidden>Reload saved score</button>
    </div>
</div>
