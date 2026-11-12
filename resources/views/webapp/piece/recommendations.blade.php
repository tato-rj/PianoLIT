@php
    $collectionRow = $recommendationRows->firstWhere('key', 'collection');
    $matchRows = $recommendationRows->reject(function ($row) { return $row['key'] === 'collection'; });
@endphp
@if($collectionRow)
<section class="discover-latest mb-4" aria-labelledby="piece-collection-heading">
    <h5 id="piece-collection-heading" class="mb-3">{{ $collectionRow['title'] }}</h5>
    <div class="discover-latest__rail custom-scroll dragscroll dragscroll-horizontal card-scroll-row" role="region" aria-labelledby="piece-collection-heading" tabindex="0">
        <div class="d-flex gap-3 pb-2">
            @foreach($collectionRow['pieces'] as $recommendationPiece)
                @include('webapp.discover.cards.latest-piece', ['piece' => $recommendationPiece])
            @endforeach
            @include('components.navigation.rail-view-all', ['url' => $collectionRow['url'], 'context' => $collectionRow['title']])
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
            <ul class="piece-recommendations-list list-unstyled d-flex flex-column gap-3 mb-3" aria-label="{{ $matchRow['title'] }}">
                @foreach($matchRow['pieces']->take(4) as $recommendationPiece)
                <li>
                    @include('webapp.discover.cards.compact-piece', ['piece' => $recommendationPiece, 'showMedia' => true])
                </li>
                @endforeach
            </ul>
            @php
                $seeMoreUrl = $matchRow['url'];
                if ($matchRow['key'] === 'same-composer') {
                    $seeMoreUrl = route('webapp.search.results', ['for' => $piece->composer->name, 'model' => \App\Composer::class]);
                } elseif (!empty($matchRow['term'])) {
                    $seeMoreUrl = route('webapp.search.results', ['for' => $matchRow['term'], 'model' => \App\Tag::class]);
                }
            @endphp
            <a class="link-primary d-inline-flex align-items-center gap-2" href="{{ $seeMoreUrl }}" aria-label="See more: {{ $matchRow['title'] }}">See more @icon('arrow-right', ['mr' => 0])</a>
            @else
            <p class="text-muted mb-0">No matching pieces found</p>
            @endif
        </div>
        @endforeach
    </div>
</section>
@endif
@include('webapp.discover.rows.link-rail-script')
