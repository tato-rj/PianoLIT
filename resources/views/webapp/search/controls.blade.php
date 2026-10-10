<div class="offcanvas offcanvas-bottom" id="search-controls" tabindex="-1" aria-labelledby="search-controls-title">
    <div class="offcanvas-body">
        <form id="search-controls-form" class="save-to-panel search-controls">
            @include('components.bottom-sheet-handle', ['label' => 'Close Sort & Filter panel'])
            <div class="save-to-panel__header">
                <div>
                    <h2 id="search-controls-title">Sort & Filter</h2>
                    <p>Refine your search to find the perfect pieces.</p>
                </div>
                <button type="button" class="btn-raw text-primary" data-search-reset>Reset</button>
            </div>
            <div class="search-controls__content">
                <fieldset class="search-controls__sort">
                    <legend>Sort by</legend>
                    <div class="search-controls__sort-options">
                        @foreach(['relevance' => ['Relevance', 'Best match'], 'title_asc' => ['Title A to Z', 'A → Z'], 'title_desc' => ['Title Z to A', 'Z → A'], 'composer' => ['Composer A to Z', 'A → Z'], 'level' => ['Level', 'Easy to difficult'], 'period' => ['Period', 'Old to modern']] as $value => $copy)
                        <label class="search-controls__sort-option">
                            <input class="form-check-input" type="radio" name="sort" value="{{$value}}" @if($value === 'relevance') checked @endif>
                            <span><strong>{{$copy[0]}}</strong><small>{{$copy[1]}}</small></span>
                        </label>
                        @endforeach
                    </div>
                </fieldset>
                <h3>Filters</h3>
                <div class="search-controls__facets">
                    @foreach(collect(\App\Services\WebApp\SearchOptions::FACETS)->except('length') as $facet => $values)
                    <fieldset data-search-facet="{{$facet}}">
                        <legend>{{ucfirst($facet)}}</legend>
                        <div class="pill-filters">
                            @foreach($values as $value)
                            <button type="button" aria-pressed="false" data-search-value="{{$value}}">{{in_array($value, ['4 hands', '6 hands', '8 hands']) ? str_replace(' ', '-', $value) : ucfirst($value)}}</button>
                            @endforeach
                        </div>
                    </fieldset>
                    @endforeach
                </div>
                <div class="search-controls__details">
                    <fieldset class="search-controls__length" data-search-facet="length">
                        <legend>Length <span data-length-summary aria-live="polite">Any length</span></legend>
                        <div class="search-length" style="--range-start: 0%; --range-end: 100%;">
                            <div class="search-length__track"><span></span></div>
                            <input type="range" min="0" max="2" step="1" value="0" aria-label="Minimum piece length" data-length-min>
                            <input type="range" min="0" max="2" step="1" value="2" aria-label="Maximum piece length" data-length-max>
                        </div>
                        <div class="search-length__labels"><span>Short</span><span>Medium</span><span>Long</span></div>
                    </fieldset>
                    <div class="search-controls__media">
                        <label class="form-check"><input type="checkbox" class="form-check-input" name="video_only" value="1"><span class="form-check-label">Show only pieces with video @icon('video', ['mr' => 0])</span></label>
                        <label class="form-check"><input type="checkbox" class="form-check-input" name="score_only" value="1"><span class="form-check-label">Show only pieces with score @icon('file-text', ['mr' => 0])</span></label>
                    </div>
                </div>
            </div>
            <div class="search-controls__footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="offcanvas">Cancel</button>
                <button type="button" class="btn btn-primary" data-search-apply>Apply</button>
            </div>
        </form>
    </div>
</div>
