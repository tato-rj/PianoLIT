<section class="piece-heading mb-4 position-relative">
    <div class="piece-header">
        <a class="piece-header-level" href="{{ route('webapp.search.results', ['search' => $piece->extended_level_name]) }}">
            @include('webapp.components.piece.level')
        </a>

        <div class="piece-header-row">
            @include('webapp.components.back', ['inline' => true])
            <h3 class="piece-header-title mb-1">{{ $piece->medium_name }}</h3>
            <div class="piece-header-actions d-flex align-items-center">
                @include('webapp.components.favorite', ['favoriteClasses' => 'piece-header-action', 'favoriteIconMargin' => 0])
                <button class="btn-raw text-body piece-header-action" type="button" aria-label="Share this piece"
                    data-bs-toggle="modal" data-bs-target="#piece-share-modal" aria-controls="piece-share-modal">
                    @icon('share', ['mr' => 0])
                </button>
                <button class="btn-raw text-body piece-header-action" type="button" aria-label="More options"
                    data-bs-toggle="offcanvas" data-bs-target="#options-panel" aria-controls="options-panel">
                    @icon('ellipsis-vertical', ['mr' => 0])
                </button>
            </div>
        </div>
        <p class="piece-header-composer text-muted">{{ $piece->attribution }}{{ $piece->composer->name }}</p>
    </div>

    <a href="{{ route('webapp.composers.show', $piece->composer) }}">
        <img src="{{ $piece->composer->cover_image }}" style="width: 100px; top: -90px; left: 30px;" class="rounded-circle shadow border border-white border-1x position-absolute piece__composer-image">
    </a>
</section>
