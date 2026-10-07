@component('components.fullscreen-modal', ['id' => $escoreModalId, 'headingId' => $escoreId.'-heading', 'classes' => 'escore-modal'])
@php
    $imageCover = !empty($escoreCoverImage);
    $coverDefaultColor = $imageCover ? \App\PDF\EscoreOptions::IMAGE_COVER_DEFAULT_COLOR : \App\PDF\EscoreOptions::DEFAULT_COLOR;
    $coverColors = ['#00a2ff' => 'Blue', '#c4e8dc' => 'Mint', '#fff0c4' => 'Cream', '#ffc4cb' => 'Rose', '#d5c4ff' => 'Lavender', '#d1d7e2' => 'Slate'];
    if ($imageCover) $coverColors = [$coverDefaultColor => 'Light gray'] + $coverColors;
@endphp
<form action="{{ $escoreUrl }}" method="POST" data-escore-form data-step="1" data-folder="{{ isset($folder) ? 'true' : 'false' }}">
    @csrf
    <input type="hidden" name="cover_style" value="modern">
    <input type="hidden" name="title_page" value="0">
    @component('components.fullscreen-modal-header', ['headingId' => $escoreId.'-heading', 'title' => 'Create eScore', 'closeLabel' => 'Close eScore editor', 'headerClasses' => 'escore-header', 'closeClasses' => 'escore-close'])
        @slot('subtitle')Your eScore will include <strong data-escore-count>{{ $scoreCount }}</strong> <span data-escore-piece-label>{{ str_plural('piece', $scoreCount) }}</span> from this {{ isset($folder) ? 'folder' : 'collection' }}.@endslot
    @endcomponent
    <span hidden data-folder-score-count>{{ $scoreCount }}</span>
    <div class="escore-body">
        <aside class="escore-controls">
            <nav class="escore-steps" aria-label="eScore creation steps">
                @foreach(['Cover', 'Pieces', 'Generate'] as $step)
                <button type="button" class="btn-raw escore-step {{ $loop->first ? 'active' : '' }}" data-escore-step="{{ $loop->iteration }}" @if($loop->first) aria-current="step" @endif><span class="escore-step__circle"><span data-step-number>{{ $loop->iteration }}</span><span data-step-check hidden>@icon('check', ['mr' => 0])</span></span><span>{{ $step }}</span></button>
                @endforeach
            </nav>
            <section data-escore-panel="1" aria-label="Cover settings">
                <fieldset class="escore-palette"><legend class="form-label">{{ !empty($escoreCoverImage) ? 'Top background color' : 'Cover style' }}</legend><div class="escore-swatches">
                    @foreach($coverColors as $color => $label)
                    <button class="escore-swatch {{ $loop->first ? 'active' : '' }}" type="button" style="--swatch-color:{{ $color }}" data-escore-color="{{ $color }}" aria-label="{{ $label }} cover" aria-pressed="{{ $loop->first ? 'true' : 'false' }}"></button>
                    @endforeach
                    <label class="escore-custom-color" title="Choose a custom color"><span class="visually-hidden">Custom cover color</span><input type="color" name="color" value="{{ $coverDefaultColor }}" aria-label="Custom cover color"></label>
                </div></fieldset>
                <div class="escore-field"><label class="form-label" for="{{ $escoreId }}-title">Title</label><input class="form-control" id="{{ $escoreId }}-title" name="title" value="{{ $escoreName }}" maxlength="160" required></div>
                <div class="escore-field"><label class="form-label" for="{{ $escoreId }}-subtitle">Subtitle <span class="text-muted">(optional)</span></label><input class="form-control" id="{{ $escoreId }}-subtitle" name="subtitle" value="A collection of pieces" maxlength="160"></div>
                <div class="escore-field"><label class="form-label" for="{{ $escoreId }}-comment">Additional line <span class="text-muted">(optional)</span></label><textarea class="form-control" id="{{ $escoreId }}-comment" name="comment" maxlength="600" rows="2">{{ $escoreDescription ?: 'for piano' }}</textarea></div>
                <div class="escore-field escore-bottom-field"><label class="form-label" for="{{ $escoreId }}-bottom">Bottom text <span class="text-muted">(optional)</span></label><input class="form-control" id="{{ $escoreId }}-bottom" name="bottom_text" value="PianoLIT eScore" maxlength="160"><p class="small text-muted mt-2 mb-0">created by {{ auth('web')->user()->full_name }}</p></div>
            </section>
            <section data-escore-panel="2" aria-label="Piece selection" hidden>
                <div class="escore-section-heading"><div><h2 class="h5 mb-1">Include pieces</h2><p class="small text-muted mb-0">Choose, reorder, and customize the table of contents.</p></div><span class="small text-muted" data-escore-selection-count>{{ $scoreCount }} of {{ $escorePieces->count() }} selected</span></div>
                <div class="escore-piece-list border rounded" data-escore-piece-list>
                    @foreach($escorePieces as $piece)
                    @php($eligible = $piece->score_path && $piece->is_public_domain && $piece->hasWebMediaAccess(auth('web')->user()))
                    <article class="escore-piece-row {{ $eligible ? 'selected' : 'unavailable' }}" data-escore-piece="{{ $piece->id }}" data-eligible="{{ $eligible ? 'true' : 'false' }}">
                        <input class="form-check-input" type="checkbox" data-escore-select aria-label="Include {{ $piece->medium_name }}" @if($eligible) checked @else disabled @endif>
                        <button class="btn-raw text-muted escore-drag" type="button" data-escore-drag aria-label="Reorder {{ $piece->medium_name }}; use up and down arrow keys" @unless($eligible) disabled @endunless>@icon('grip-vertical', ['mr' => 0])</button>
                        <span class="small text-muted" data-escore-row-number>{{ $loop->iteration }}</span>
                        <div class="escore-piece-identity"><span data-escore-piece-title>{{ $piece->medium_name }}</span><span class="text-muted" data-escore-piece-composer> · {{ $piece->composer->short_name }}</span>@unless($eligible)<small class="text-muted d-block">{{ !$piece->score_path ? 'Score unavailable' : (!$piece->is_public_domain ? 'Public-domain score required' : 'Subscription required') }}</small>@endunless</div>
                        <span class="small text-muted escore-piece-duration" data-escore-duration>—</span>
                    </article>
                    @endforeach
                </div>
                <p class="small text-muted mt-2">Drag pieces to reorder them. This order applies to your eScore.</p>
            </section>
            <section data-escore-panel="3" aria-label="Final details" hidden>
                <h2 class="h5 mb-1">Final details</h2><p class="small text-muted mb-3">Review your eScore and make final adjustments.</p>
                @foreach([
                    'page_numbers' => ['Page numbers', 'Show page numbers in the eScore', true],
                    'composer_names' => ['Composer names', 'Show composer names in the table of contents', true],
                    'include_edition' => ['Include “About this edition” page', 'Add a page with information about this collection', true],
                    'blank_pages' => ['Include blank pages between pieces', 'Add a blank page before each new piece', false],
                ] as $name => $setting)
                <label class="escore-setting border rounded" for="{{ $escoreId }}-{{ $name }}"><span><span class="d-block">{{ $setting[0] }}</span><span class="small text-muted d-block mt-1">{{ $setting[1] }}</span></span><span class="form-check form-switch m-0"><input class="form-check-input" id="{{ $escoreId }}-{{ $name }}" type="checkbox" name="{{ $name }}" value="1" @if($setting[2]) checked @endif></span></label>
                @endforeach
                <div class="escore-edition-notes escore-field" data-escore-notes><label class="form-label" for="{{ $escoreId }}-notes">About this edition</label><textarea class="form-control" id="{{ $escoreId }}-notes" name="edition_notes" rows="2" maxlength="4000">{{ $escoreDescription ?: 'A personal collection of piano pieces, selected from my repertoire.' }}</textarea></div>
                <div class="escore-paper-size border-top pt-3 mt-3"><label class="form-label" for="{{ $escoreId }}-size">Page size</label><select class="form-select" name="page_size" id="{{ $escoreId }}-size"><option value="letter">US Letter (8.5 × 11 in)</option><option value="a4">A4 (210 × 297 mm)</option></select></div>
            </section>
        </aside>
        <section class="escore-preview" aria-label="Document preview">
            <div class="escore-preview-heading"><div><h2 class="h5 mb-1">Preview</h2><p class="small text-muted mb-0" data-escore-preview-caption>This is how your eScore will look.</p></div>@include('webapp.playlist.escore-tabs')</div>
            <div class="escore-stage">
                <div class="escore-preview-loader" data-escore-loader role="status"><span class="escore-preview-loader__ring" aria-hidden="true"></span><span>Preparing preview…</span></div>
                <div class="escore-page escore-book shadow-dark escore-page--pending" data-escore-main-page aria-busy="true">
                    <figure class="escore-cover-preview {{ !empty($escoreCoverImage) ? 'escore-cover-preview--image' : '' }}" data-escore-cover @if(!empty($escoreCoverImage)) data-escore-image-cover="true" @endif aria-label="Cover preview">
                        @if(!empty($escoreCoverImage))<img class="escore-cover-preview__image" src="{{ $escoreCoverImage }}" alt="">@endif
                        <div class="escore-cover-preview__title" data-escore-preview="title">{{ $escoreName }}</div><div class="escore-cover-preview__subtitle" data-escore-preview="subtitle">A collection of pieces</div><div class="escore-cover-preview__description" data-escore-preview="comment">{{ $escoreDescription ?: 'for piano' }}</div><div class="escore-cover-preview__brand"><span data-escore-preview="bottom_text">PianoLIT eScore</span><small>created by {{ auth('web')->user()->full_name }}</small></div>
                    </figure>
                    <canvas data-escore-main-canvas hidden aria-label="Preview page"></canvas>
                </div>
            </div>
            <div class="escore-pager"><button type="button" class="btn btn-secondary" data-escore-previous-page aria-label="Previous preview page" disabled>@icon('chevron-left', ['mr' => 0])</button><span class="text-muted" data-escore-page-label>1 / —</span><button type="button" class="btn btn-secondary" data-escore-next-page aria-label="Next preview page" disabled>@icon('chevron-right', ['mr' => 0])</button></div>
        </section>
        <aside class="escore-overview">
            <div data-escore-thumbnail-panel><div class="escore-overview-heading"><h2 class="h5 mb-1">Preview</h2><p class="small text-muted mb-3">This is how your eScore will look.</p>@include('webapp.playlist.escore-tabs')</div><div class="escore-thumbnails" data-escore-thumbnails aria-label="Preview pages"></div></div>
            <div data-escore-summary hidden><h2 class="h5 mb-1">eScore summary</h2><p class="small text-muted mb-3" data-escore-summary-label>{{ $scoreCount }} pieces · Calculating pages</p><div class="escore-summary-pieces border rounded" data-escore-summary-pieces></div><div class="escore-page-breakdown"><h3 class="h6 mb-3">Pages</h3><div data-escore-breakdown></div><div class="escore-total border-top mt-3 pt-3"><strong>Total</strong><strong data-escore-total>— pages</strong></div></div></div>
        </aside>
    </div>
    <footer class="escore-footer">
        <button class="btn btn-secondary" type="button" data-escore-back hidden>@icon('arrow-left', ['mr' => 0])<span data-escore-back-label>Back to cover</span><span class="escore-back-short" aria-hidden="true">Back</span></button>
        <button class="btn btn-secondary btn-sm" type="button" data-escore-retry hidden>Retry preview</button>
        <div class="escore-footer-primary">
            <div class="escore-status small text-muted" role="status" aria-live="polite" data-escore-status>Select a cover style to begin.</div>
            <button class="btn btn-primary escore-continue" type="button" data-escore-next @unless($scoreCount) disabled @endunless><span data-escore-next-label>Continue to pieces</span><span class="escore-next-short" aria-hidden="true">Continue</span><span data-escore-next-icon>@icon('arrow-right', ['mr' => 0])</span></button>
        </div>
    </footer>
</form>
@endcomponent
@once
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@2.3.200/build/pdf.min.js"></script>
<script src="{{ mix('js/views/escore.js') }}"></script>
@endpush
@endonce
