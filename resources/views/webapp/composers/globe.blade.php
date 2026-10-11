@component('components.fullscreen-modal', ['id' => 'composer-globe-modal', 'headingId' => 'composer-globe-heading', 'data' => ['globe-library' => mix('js/vendor/globe.gl.min.js'), 'globe-map' => mix('data/composer-globe.geojson'), 'globe-catalogue' => route('webapp.composers.globe')]])
    @include('components.fullscreen-modal-header', ['headingId' => 'composer-globe-heading', 'title' => 'Explore the globe', 'subtitle' => 'A world of piano music, waiting to be discovered.', 'closeLabel' => 'Close Explore the globe'])
    <div class="modal-body composer-globe-layout">
        <section class="composer-globe-stage" aria-label="Interactive world globe">
            <div class="composer-globe-mode"><span class="composer-globe-dot"></span><span data-globe-mode>Continents</span></div>
            <div class="composer-globe-legend" data-globe-legend hidden><span></span>Countries with music in our library</div>
            <div class="composer-globe-canvas" data-globe-canvas tabindex="0" role="group" aria-label="World globe. Drag to rotate, scroll or pinch to zoom. Arrow keys rotate; plus and minus zoom." aria-describedby="composer-globe-help"></div>
            <div class="composer-globe-loading" data-globe-loading role="status">
                <span class="spinner-border spinner-border-sm" aria-hidden="true"></span><span>Preparing your world…</span>
            </div>
            <div class="composer-globe-error" data-globe-error role="alert" hidden>
                <p data-globe-error-message>The globe could not load. Please try again.</p>
                <button class="btn btn-secondary btn-sm" type="button" data-globe-retry>Try again</button>
            </div>
            <div class="composer-globe-tools" role="group" aria-label="Globe controls">
                <button type="button" data-globe-zoom-in aria-label="Zoom in" title="Zoom in">@icon('plus', ['mr' => 0])</button>
                <button type="button" data-globe-zoom-out aria-label="Zoom out" title="Zoom out">@icon('minus', ['mr' => 0])</button>
                <span></span>
                <button type="button" data-globe-home aria-label="Reset globe to world view" title="World view">@icon('globe', ['mr' => 0])</button>
            </div>
            <p class="composer-globe-help" id="composer-globe-help">Drag to rotate <span>·</span> Scroll or pinch to zoom <span>·</span> Select a place to explore</p>
            <span class="composer-globe-credit">Map: Natural Earth</span>
        </section>
        <aside class="composer-globe-panel" aria-label="Explore places">
            <label class="composer-globe-eyebrow" for="composer-globe-place">Jump to a place</label>
            <select class="form-select" id="composer-globe-place" data-globe-place disabled><option value="world">The whole world</option></select>
            <div class="composer-globe-details" aria-live="polite" aria-atomic="true">
                <p class="composer-globe-eyebrow" data-globe-kind>OUR LIBRARY, WORLDWIDE</p>
                <h2 data-globe-title>A world of music</h2>
                <p class="composer-globe-description" data-globe-description>Select a continent, then zoom closer to discover its countries.</p>
                <div class="composer-globe-stats">
                    <div><strong data-globe-composers>—</strong><span data-globe-composer-label>composers</span></div>
                    <div><strong data-globe-pieces>—</strong><span data-globe-piece-label>pieces</span></div>
                </div>
            </div>
            <div class="composer-globe-portrait-status" data-globe-portrait-status role="status" hidden>
                <span data-globe-portrait-message></span>
                <button type="button" data-globe-portrait-retry hidden>Try again</button>
            </div>
            <a class="btn btn-primary composer-globe-browse" data-globe-browse hidden>Browse composers @icon('arrow-right', ['mr' => 0])</a>
            <button class="btn btn-secondary composer-globe-closer" type="button" data-globe-closer hidden>Explore countries @icon('zoom-in', ['mr' => 0])</button>
            <div class="composer-globe-regions">
                <h3 class="composer-globe-eyebrow" data-globe-list-title>Choose a continent</h3>
                <div data-globe-regions></div>
            </div>
            <p class="composer-globe-footnote">Counts follow each composer’s recorded country in our library.<span data-globe-unmapped hidden> Some composers have no recorded continent and are included in world totals only.</span></p>
        </aside>
    </div>
@endcomponent
