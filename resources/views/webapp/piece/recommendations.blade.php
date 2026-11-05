@php
    $collectionRow = $recommendationRows->firstWhere('key', 'collection');
    $matchRows = $recommendationRows->reject(function ($row) { return $row['key'] === 'collection'; });
@endphp
@if($collectionRow)
<section class="discover-latest mb-4" aria-labelledby="piece-collection-heading">
    <div class="d-flex d-apart gap-3 mb-3">
        <h5 id="piece-collection-heading" class="m-0">{{ $collectionRow['title'] }}</h5>
        <a href="{{ $collectionRow['url'] }}" class="btn-raw link-primary" aria-label="View all: {{ $collectionRow['title'] }}">View all</a>
    </div>
    <div class="discover-latest__rail custom-scroll dragscroll dragscroll-horizontal card-scroll-row" role="region" aria-labelledby="piece-collection-heading" tabindex="0">
        <div class="d-flex gap-3 pb-2">
            @foreach($collectionRow['pieces'] as $recommendationPiece)
                @include('webapp.discover.cards.latest-piece', ['piece' => $recommendationPiece])
            @endforeach
        </div>
    </div>
</section>
@endif
@if($matchRows->contains(function ($row) { return $row['pieces']->isNotEmpty(); }))
<section class="discover-for-you mb-4" aria-labelledby="piece-recommendations-heading">
    <h5 id="piece-recommendations-heading" class="mb-3">More like this</h5>
    @include('components.navigation.pill-tabs', [
        'id' => 'piece-recommendations', 'label' => 'More like this', 'active' => 'best-matches', 'class' => 'mb-3', 'scrollable' => true,
        'items' => $matchRows->map(function ($row) {
            return ['key' => $row['key'], 'label' => $row['title'], 'disabled' => $row['key'] !== 'best-matches' && $row['pieces']->isEmpty()];
        }),
    ])
    <div class="tab-content">
        @foreach($matchRows as $matchRow)
        <div class="tab-pane {{ $matchRow['key'] === 'best-matches' ? 'active' : '' }}" id="piece-recommendations-{{ $matchRow['key'] }}" role="tabpanel" aria-labelledby="piece-recommendations-{{ $matchRow['key'] }}-tab" tabindex="0">
            @if($matchRow['pieces']->isNotEmpty())
            <div class="d-flex justify-content-end mb-2">
                <a href="{{ $matchRow['url'] }}" class="btn-raw link-primary" aria-label="View all: {{ $matchRow['title'] }}">View all</a>
            </div>
            <div class="discover-for-you__rail custom-scroll dragscroll dragscroll-horizontal card-scroll-row" role="region" aria-label="{{ $matchRow['title'] }}" tabindex="0">
                <div class="d-flex gap-3 pb-2">
                    @foreach($matchRow['pieces'] as $recommendationPiece)
                        @include('webapp.discover.cards.compact-piece', ['piece' => $recommendationPiece, 'showMedia' => true])
                    @endforeach
                </div>
            </div>
            @else
            <p class="text-muted mb-0">No matching pieces found</p>
            @endif
        </div>
        @endforeach
    </div>
</section>
@endif
@include('webapp.discover.rows.link-rail-script')
