<div class="row g-4 align-items-center mb-4">
    <div class="col-md-4 col-lg-3">
        <img src="{{ $composer->cover_image }}" alt="Portrait of {{ $composer->name }}" class="composer-profile-portrait rounded w-100 d-block" fetchpriority="high">
    </div>
    <div class="col-md-8 col-lg-9">
        <p class="small text-muted text-uppercase fw-semibold mb-0">Composer</p>
        <h3 id="composer-name" class="mb-1">{{ $composer->name }}</h3>
        @if($composer->lifespan)
        <p class="text-muted mb-2">{{ $composer->lifespan }}</p>
        @endif
        @if($composer->country)
        <p class="text-muted mb-3">
            @flag(['code' => $composer->country->flag_code])
            {{ $composer->country->name }}
        </p>
        @endif
        <div class="d-grid d-md-flex">
            <a href="{{ route('webapp.search.results', ['search' => $composer->name]) }}" class="btn btn-secondary d-inline-flex align-items-center justify-content-center gap-3">
                @icon('search', ['mr' => 0])Discover pieces @icon('chevron-right', ['mr' => 0])
            </a>
        </div>
    </div>
</div>

<hr class="d-lg-none my-4">
<div class="row g-4 pt-lg-2">
    @if($composer->curiosity)
    <div class="col-lg-5 order-lg-2">
        <aside class="bg-light border rounded p-4" aria-labelledby="composer-curiosity-title">
            <div class="d-flex align-items-center gap-3 mb-3">
                <span class="bg-orange-lightest text-orange rounded-circle p-3 d-inline-flex">@icon('lightbulb', ['size' => 'xl', 'mr' => 0])</span>
                <h2 id="composer-curiosity-title" class="h6 text-uppercase mb-0">Did you know?</h2>
            </div>
            <p class="mb-0">{{ $composer->curiosity }}</p>
        </aside>
    </div>
    @endif
    @if($composer->biography)
    <div class="{{ $composer->curiosity ? 'col-lg-7' : 'col-12' }} order-lg-1">
        <section aria-labelledby="composer-biography-title">
            <p class="composer-biography mb-0">{{ $composer->biography }}</p>
        </section>
    </div>
    @endif
</div>
