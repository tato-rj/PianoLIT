<a href="{{ route('webapp.search.results', ['search' => $tagName]) }}" class="explore-tag explore-tag--{{ $kind ?? 'simple' }} border rounded link-none hover-shadow">
    @if(($kind ?? null) === 'period')
    <img src="{{ $tag->web_cover_image }}" alt="" class="explore-period-art" loading="lazy">
    @endif
    <span class="explore-tag-copy d-flex align-items-center gap-3">
        @if(($kind ?? null) === 'level')
            @icon('circle', ['classes' => 'color-'.lastword($tagName).' flex-shrink-0', 'filled' => true, 'mr' => 0])
        @elseif(($kind ?? null) === 'mood')
            @icon($moodIcons[$tagName], ['classes' => 'text-primary flex-shrink-0', 'size' => 'lg', 'mr' => 0])
        @endif
        <span class="flex-grow-1">
            <span class="{{ in_array($kind ?? null, ['period', 'level']) ? 'fw-bold' : '' }}">{{ ucwords($tagName) }}</span>
            @if(in_array($kind ?? null, ['period', 'level']))
            <span class="d-block text-muted small">{{ $tag->pieces_count }} {{ str_plural('piece', $tag->pieces_count) }}</span>
            @endif
        </span>
        @unless(($kind ?? null) === 'mood')
        @icon('arrow-right', ['classes' => 'explore-tag-arrow text-muted flex-shrink-0', 'mr' => 0])
        @endunless
    </span>
</a>
