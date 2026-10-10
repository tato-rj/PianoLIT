<div class="offcanvas offcanvas-bottom" id="composer-controls" tabindex="-1" aria-labelledby="composer-controls-title">
    <div class="offcanvas-body">
        <form class="save-to-panel search-controls" id="composer-controls-form">
            @include('components.bottom-sheet-handle', ['label' => 'Close composer Sort & Filter panel'])
            <div class="save-to-panel__header">
                <div>
                    <h2 id="composer-controls-title">Sort & Filter</h2>
                    <p>Refine your search to find composers.</p>
                </div>
                <button type="button" class="btn-raw text-primary" data-composer-panel-reset>Reset</button>
            </div>
            <div class="search-controls__content">
                <fieldset class="search-controls__sort">
                    <legend>Sort by</legend>
                    <div class="search-controls__sort-options">
                        @foreach(['pieces' => ['Most pieces', 'Largest repertoire first'], 'name' => ['Last name A–Z', 'A → Z'], 'recent' => ['Recently added', 'Newest additions first'], 'period' => ['Period', 'Old to modern']] as $value => $copy)
                        <label class="search-controls__sort-option">
                            <input class="form-check-input" type="radio" name="composer_sort" value="{{ $value }}" @if($value === 'pieces') checked @endif>
                            <span><strong>{{ $copy[0] }}</strong><small>{{ $copy[1] }}</small></span>
                        </label>
                        @endforeach
                    </div>
                </fieldset>
                <h3>Filters</h3>
                <div class="search-controls__facets">
                    @foreach(['period' => \App\Services\WebApp\SearchOptions::FACETS['period'], 'continent' => ['africa', 'antarctica', 'asia', 'europe', 'north america', 'oceania', 'south america'], 'gender' => ['female', 'male']] as $facet => $values)
                    <fieldset data-composer-facet="{{ $facet }}">
                        <legend>{{ ucfirst($facet) }}</legend>
                        <div class="pill-filters">
                            @foreach($values as $value)
                            <button type="button" aria-pressed="false" data-composer-value="{{ $value }}">{{ ucwords($value) }}</button>
                            @endforeach
                        </div>
                    </fieldset>
                    @endforeach
                </div>
            </div>
            <div class="search-controls__footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="offcanvas">Cancel</button>
                <button type="button" class="btn btn-primary" data-composer-apply data-bs-dismiss="offcanvas">Apply</button>
            </div>
        </form>
    </div>
</div>
