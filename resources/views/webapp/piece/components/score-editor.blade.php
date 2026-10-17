@php
    $scorePreview = isset($hasMediaAccess) && ! $hasMediaAccess;
    $accessPolicy = app(\App\Services\WebApp\AccessPolicy::class);
    $scorePromptTarget = $accessPolicy->promptTarget(auth('web')->user(), 'score', $piece);
    $scoreUpgradeAttributes = 'data-access-action="score" data-bs-toggle="modal" data-bs-target="'.$scorePromptTarget.'"';
    $scoreExportAttributes = 'data-access-action="score-export" data-bs-toggle="modal" data-bs-target="'.$accessPolicy->promptTarget(auth('web')->user(), 'score-export', $piece).'"';
    $scoreButtonAttributes = $scorePreview ? $scoreUpgradeAttributes : 'disabled';
    $scoreAnnotationAttributes = !$hasAnnotationAccess ? 'data-access-action="annotations" data-bs-toggle="modal" data-bs-target="'.$accessPolicy->promptTarget(auth('web')->user(), 'annotations', $piece).'"' : 'disabled';
    $scoreDropdownAttributes = !$hasAnnotationAccess ? $scoreAnnotationAttributes : 'data-bs-toggle="dropdown" disabled';
@endphp
<div id="{{ $scorePreview ? 'score-preview' : 'score-editor' }}" class="score-editor {{ $scorePreview ? 'score-editor--preview' : '' }} mb-4" data-pdf-url="{{ storage($piece->score_path) }}"
    data-score-export-access="{{ $hasScoreExportAccess ? '1' : '0' }}"
    data-score-version="{{ hash('sha256', $piece->score_path) }}"
    data-annotations-url="{{ $hasAnnotationAccess ? route('webapp.pieces.score.annotations.show', $piece) : '' }}">
    <div class="score-toolbar" role="toolbar" aria-label="Score annotation tools">
        <div class="score-tools" role="group" aria-label="Drawing tools">
            <button type="button" data-tool="pen" data-edit-control class="score-icon-button" aria-label="Pen" title="Pen" aria-pressed="false" {!! $scoreAnnotationAttributes !!}>@icon('pencil', ['mr' => 0])</button>
            <button type="button" data-tool="text" data-edit-control class="score-icon-button" aria-label="Text" title="Text" aria-pressed="false" {!! $scoreAnnotationAttributes !!}>@icon('text-cursor', ['mr' => 0])</button>
            <button type="button" data-tool="erase" data-edit-control class="score-icon-button" aria-label="Eraser" title="Eraser" aria-pressed="false" {!! $scoreAnnotationAttributes !!}>@icon('eraser', ['mr' => 0])</button>
            <button type="button" data-tool="highlight" data-edit-control class="score-icon-button" aria-label="Highlighter" title="Highlighter" aria-pressed="false" {!! $scoreAnnotationAttributes !!}>@icon('highlighter', ['mr' => 0])</button>
            <div class="dropdown score-color-control">
                <button type="button" id="score-color-picker" data-edit-control class="score-color-button score-icon-button" data-bs-auto-close="outside" aria-label="Annotation color" aria-haspopup="true" aria-expanded="false" title="Annotation color" {!! $scoreDropdownAttributes !!}>@icon('palette', ['mr' => 0])</button>
                <div class="dropdown-menu score-color-menu" aria-labelledby="score-color-picker">
                    <div class="score-color-swatches" role="group" aria-label="Writing colors">
                        @foreach(['#20252b' => 'Black', '#e53935' => 'Red', '#f57c00' => 'Orange', '#fbc02d' => 'Yellow', '#2eaf4a' => 'Green', '#1565d8' => 'Blue', '#8e44ad' => 'Purple', '#e14d9b' => 'Pink'] as $color => $label)
                            <button type="button" data-color-option="{{ $color }}" data-edit-control class="score-color-swatch" style="background-color: {{ $color }}; color: {{ $label === 'Yellow' ? '#172033' : '#fff' }}" aria-label="{{ $label }}" aria-pressed="{{ $color === '#20252b' ? 'true' : 'false' }}" disabled><span data-color-check @if($color !== '#20252b') hidden @endif>@icon('check', ['mr' => 0])</span></button>
                        @endforeach
                    </div>
                    <label class="score-custom-color mb-0">Custom color<input data-color data-edit-control type="color" value="#20252b" class="score-color-input" aria-label="Custom annotation color" disabled></label>
                </div>
            </div>
            <div class="dropdown score-width-control">
                <input type="hidden" data-width value="0.004">
                <button type="button" id="score-pen-width" data-edit-control class="score-icon-button score-width-toggle" aria-label="Pen thickness" aria-haspopup="true" aria-expanded="false" title="Pen thickness" {!! $scoreDropdownAttributes !!}>
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
            <button type="button" data-undo class="score-icon-button" aria-label="Undo" title="Undo" {!! $scoreAnnotationAttributes !!}>@icon('undo-2', ['mr' => 0])</button>
            <button type="button" data-redo class="score-icon-button" aria-label="Redo" title="Redo" {!! $scoreAnnotationAttributes !!}>@icon('rotate-cw', ['mr' => 0])</button>
            <button type="button" data-clear-all data-edit-control class="score-clear-button" aria-label="Clear all markings" title="Clear all markings" {!! $scoreAnnotationAttributes !!}>@icon('trash-2', ['mr' => 0])<span>Clear all</span></button>
        </div>
    </div>
    <div class="score-status-row"><span data-score-status class="score-save-status {{ $scorePreview ? 'score-preview-status' : '' }}" role="status" aria-live="polite">Loading score…</span></div>

    <div class="score-scroll">
        <div class="score-sheet">
            @if($scorePreview)
                <div class="score-preview-pages" aria-hidden="true"></div>
                <div class="score-preview-upgrade text-center">
                    @guest('web')
                        @if($accessPolicy->snapshot()['rules'][$piece->is_free ? 'free_pick' : 'regular']['score']['registered'] === 'available')
                            <p>This score is ready to explore with a <strong>free</strong> PianoLIT account.</p>
                        @elseif($accessPolicy->snapshot()['rules']['free_pick']['score']['registered'] === 'available')
                            <p>A <strong>free</strong> account brings you this week’s score and more to explore.</p>
                        @else
                            <p>There’s more music to explore with a <strong>free</strong> PianoLIT account.</p>
                        @endif
                        <a href="{{ route('register') }}" class="btn btn-primary">@icon('user')Sign up <strong>free</strong></a>
                    @else
                        @if($scorePromptTarget === '#piece-upgrade-modal')
                            <p>The full score is waiting for you with PianoLIT Premium.</p>
                            <button type="button" {!! $scoreButtonAttributes !!} class="btn btn-primary">@icon('crown')Go Premium</button>
                        @else
                            <p>This score is currently locked.</p>
                        @endif
                    @endguest
                </div>
            @else
            <canvas id="score-pdf" aria-label="Score PDF page"></canvas>
            <svg class="score-markings" aria-label="Draw or place markings on this score" role="img"></svg>
            @endif
        </div>
    </div>

    <div class="score-footer" role="toolbar" aria-label="Score view controls">
        <div class="score-view-controls">
            <div class="score-control-group" role="group" aria-label="Score page">
                <button type="button" data-prev class="score-control-button" aria-label="Previous score page" {!! $scoreButtonAttributes !!}>@icon('chevron-left', ['mr' => 0])</button>
                <span data-page-label class="score-page-label" aria-live="polite">Page &mdash;</span>
                <button type="button" data-next class="score-control-button" aria-label="Next score page" {!! $scoreButtonAttributes !!}>@icon('chevron-right', ['mr' => 0])</button>
            </div>
            <div class="d-flex" style="gap: 8px">
                <div class="score-control-group" role="group" aria-label="Score zoom">
                    <button type="button" data-zoom="-0.25" class="score-control-button" aria-label="Zoom out" {!! $scoreButtonAttributes !!}>&minus;</button>
                    <span data-zoom-label class="score-zoom-label">75%</span>
                    <button type="button" data-zoom="0.25" class="score-control-button" aria-label="Zoom in" {!! $scoreButtonAttributes !!}>+</button>
                </div>

            <button type="button" data-fullscreen class="score-control-button score-fullscreen-button" aria-label="Full screen" title="Full screen" @if($scorePreview) {!! $scoreButtonAttributes !!} @endif>@icon('maximize', ['mr' => 0])</button>
            </div>
        </div>
        <div class="score-footer-actions">
            @if($piece->hasAudio())
                <button type="button" @if($audioPreviewSeconds === 0) data-bs-toggle="modal" data-bs-target="{{ $accessPolicy->promptTarget(auth('web')->user(), 'audio', $piece) }}" @else id="launch-audio" data-url="{{ route('webapp.pieces.audio', $piece) }}" @endif data-access-action="audio" class="score-listen-button">@icon('play', ['mr' => 0])<span>Listen</span></button>
            @endif
            @if(! $hasScoreExportAccess)
                <button type="button" {!! $scoreExportAttributes !!} class="score-action-button" aria-label="Download score" title="Download the original score without markings">@icon('download', ['mr' => 0])<span>Download</span></button>
            @else
            <a href="{{ storage($piece->score_path) }}" target="_blank" rel="noopener" class="score-action-button" aria-label="Download score" title="Download the original score without markings">@icon('download', ['mr' => 0])<span>Download</span></a>
            @endif
            <button type="button" data-print class="score-action-button" title="Print all score pages with markings" @if(! $hasScoreExportAccess) {!! $scoreExportAttributes !!} @endif>@icon('printer', ['mr' => 0])<span>Print</span></button>
        </div>
    </div>
    <div class="score-messages">
        <button type="button" data-retry-load class="btn btn-sm btn-light" hidden>Retry loading</button>
        <button type="button" data-retry-save class="btn btn-sm btn-light" hidden>Retry saving</button>
        <button type="button" data-reload-score class="btn btn-sm btn-light" hidden>Reload saved score</button>
    </div>
</div>
