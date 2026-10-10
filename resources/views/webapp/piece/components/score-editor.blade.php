<div id="score-editor" class="score-editor mb-4" data-pdf-url="{{ storage($piece->score_path) }}"
    data-score-version="{{ hash('sha256', $piece->score_path) }}"
    data-annotations-url="{{ auth('web')->check() ? route('webapp.pieces.score.annotations.show', $piece) : '' }}">
    <div class="score-toolbar @guest('web') d-none @endguest" role="toolbar" aria-label="Score annotation tools">
        <div class="score-tools" role="group" aria-label="Drawing tools">
            <button type="button" data-tool="pen" data-edit-control class="score-icon-button" aria-label="Pen" title="Pen" aria-pressed="false" disabled>@icon('pencil', ['mr' => 0])</button>
            <button type="button" data-tool="text" data-edit-control class="score-icon-button" aria-label="Text" title="Text" aria-pressed="false" disabled>@icon('text-cursor', ['mr' => 0])</button>
            <button type="button" data-tool="erase" data-edit-control class="score-icon-button" aria-label="Eraser" title="Eraser" aria-pressed="false" disabled>@icon('eraser', ['mr' => 0])</button>
            <button type="button" data-tool="highlight" data-edit-control class="score-icon-button" aria-label="Highlighter" title="Highlighter" aria-pressed="false" disabled>@icon('highlighter', ['mr' => 0])</button>
            <label class="score-color-button score-icon-button mb-0" title="Annotation color">
                @icon('palette', ['mr' => 0])
                <input data-color data-edit-control type="color" value="#20252b" class="score-color-input" aria-label="Annotation color" disabled>
            </label>
            <div class="dropdown score-width-control">
                <input type="hidden" data-width value="0.004">
                <button type="button" id="score-pen-width" data-edit-control class="score-icon-button score-width-toggle" data-bs-toggle="dropdown" aria-label="Pen thickness" aria-haspopup="true" aria-expanded="false" title="Pen thickness" disabled>
                    <svg viewBox="0 0 120 32" aria-hidden="true"><path data-width-preview d="M12 20 C40 28 78 4 108 14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" /></svg>
                </button>
                <div class="dropdown-menu score-width-menu" role="menu" aria-labelledby="score-pen-width">
                    @foreach([
                        ['value' => '0.001', 'sample' => '0.8', 'label' => 'Extra fine'],
                        ['value' => '0.002', 'sample' => '1.4', 'label' => 'Fine'],
                        ['value' => '0.004', 'sample' => '2.2', 'label' => 'Medium'],
                        ['value' => '0.006', 'sample' => '3.5', 'label' => 'Broad'],
                        ['value' => '0.009', 'sample' => '5', 'label' => 'Thick'],
                        ['value' => '0.013', 'sample' => '7', 'label' => 'Extra thick'],
                        ['value' => '0.018', 'sample' => '10', 'label' => 'Bold'],
                    ] as $width)
                        <button type="button" data-width-option="{{ $width['value'] }}" data-edit-control class="dropdown-item score-width-option {{ $width['value'] === '0.004' ? 'active' : '' }}" role="menuitemradio" aria-label="{{ $width['label'] }}" aria-checked="{{ $width['value'] === '0.004' ? 'true' : 'false' }}" disabled>
                            <span class="score-width-check" aria-hidden="true"><span data-width-check @if($width['value'] !== '0.004') hidden @endif>@icon('check', ['mr' => 0])</span></span>
                            <svg viewBox="0 0 120 32" aria-hidden="true"><path data-width-sample d="M12 20 C40 28 78 4 108 14" fill="none" stroke="currentColor" stroke-width="{{ $width['sample'] }}" stroke-linecap="round" /></svg>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="score-history" role="group" aria-label="Marking history">
            <button type="button" data-undo class="score-icon-button" aria-label="Undo" title="Undo" disabled>@icon('undo-2', ['mr' => 0])</button>
            <button type="button" data-redo class="score-icon-button" aria-label="Redo" title="Redo" disabled>@icon('rotate-cw', ['mr' => 0])</button>
            <button type="button" data-clear-all data-edit-control class="score-clear-button" aria-label="Clear all markings" title="Clear all markings" disabled>@icon('trash-2', ['mr' => 0])<span>Clear all</span></button>
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
                <button type="button" data-prev class="score-control-button" aria-label="Previous score page" disabled>@icon('chevron-left', ['mr' => 0])</button>
                <span data-page-label class="score-page-label" aria-live="polite">Page &mdash;</span>
                <button type="button" data-next class="score-control-button" aria-label="Next score page" disabled>@icon('chevron-right', ['mr' => 0])</button>
            </div>
            <div class="d-flex" style="gap: 8px">
                <div class="score-control-group" role="group" aria-label="Score zoom">
                    <button type="button" data-zoom="-0.25" class="score-control-button" aria-label="Zoom out" disabled>&minus;</button>
                    <span data-zoom-label class="score-zoom-label">75%</span>
                    <button type="button" data-zoom="0.25" class="score-control-button" aria-label="Zoom in" disabled>+</button>
                </div>

            <button type="button" data-fullscreen class="score-control-button score-fullscreen-button" aria-label="Full screen" title="Full screen">@icon('maximize', ['mr' => 0])</button>
            </div>
        </div>
        <div class="score-footer-actions">
            @if($piece->hasAudio())
                <button type="button" id="launch-audio" data-url="{{ route('webapp.pieces.audio', $piece) }}" class="score-listen-button">@icon('play', ['mr' => 0])<span>Listen</span></button>
            @endif
            <a href="{{ storage($piece->score_path) }}" target="_blank" rel="noopener" class="score-action-button" aria-label="Download score" title="Download the original score without markings">@icon('download', ['mr' => 0])<span>Download</span></a>
            <button type="button" data-print class="score-action-button" title="Print all score pages with markings">@icon('printer', ['mr' => 0])<span>Print</span></button>
        </div>
    </div>
    <div class="score-messages">
        <button type="button" data-retry-load class="btn btn-sm btn-light" hidden>Retry loading</button>
        <button type="button" data-retry-save class="btn btn-sm btn-light" hidden>Retry saving</button>
        <button type="button" data-reload-score class="btn btn-sm btn-light" hidden>Reload saved score</button>
    </div>
</div>
