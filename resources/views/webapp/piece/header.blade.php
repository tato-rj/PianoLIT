<section class="piece-heading mb-4 position-relative">
    <div class="piece-header">
        <a class="piece-header-level" href="{{ route('webapp.search.results', ['search' => $piece->extended_level_name]) }}">
            @include('webapp.components.piece.level')
        </a>

        <h3 class="piece-header-title mb-1">{{ $piece->medium_name }}</h3>
        <p class="piece-header-composer text-muted">{{ $piece->attribution }}{{ $piece->composer->name }}</p>
    </div>

    <a href="{{ route('webapp.composers.show', $piece->composer) }}">
        <img src="{{ $piece->composer->cover_image }}" style="width: 100px; top: -90px; left: 30px;" class="rounded-circle shadow border border-white border-1x position-absolute piece__composer-image">
    </a>
</section>
