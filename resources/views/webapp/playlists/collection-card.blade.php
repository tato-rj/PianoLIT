<a class="collection-card {{ $expanded ? 'collection-card--expanded' : '' }}" href="{{ route('webapp.playlists.show', $card['playlist']) }}" @unless($expanded) data-collection-category="{{ $card['category'] }}" @endunless>
    <div class="collection-card__art {{ $card['illustrated'] ? 'collection-card__art--illustrated' : '' }}">
        <img src="{{ $card['image'] }}" alt="" width="900" height="600" loading="lazy" data-collection-image data-fallback="{{ asset('images/webapp/collections/featured.webp') }}">
        @if($card['illustrated'])
        <span aria-hidden="true">{{ $card['playlist']->name }}</span>
        @endif
    </div>
    <div class="collection-card__copy">
        <h3>{{ $card['playlist']->name }}</h3>
        @if($expanded && $card['playlist']->subtitle)
        <p>{{ $card['playlist']->subtitle }}</p>
        @endif
        <span class="collections-meta">{{ $card['playlist']->pieces_count }} pieces</span>
        @if($expanded)
        <span class="collection-card__action">Explore collection @icon('arrow-right', ['mr' => 0])</span>
        @endif
    </div>
</a>
