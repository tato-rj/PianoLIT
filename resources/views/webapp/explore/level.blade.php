<nav class="d-flex {{ empty($breadcrumbs) ? 'd-lg-none' : '' }} flex-wrap align-items-center gap-2 mb-3 mt-4 mt-lg-0" aria-label="Explore path">
    <a class="d-lg-none" href="{{ route('webapp.explore') }}">@icon('chevron-left', ['mr' => 0]) Explore</a>
    @foreach($breadcrumbs as $breadcrumb)
        <a href="{{ route('webapp.explore', $breadcrumb['params']) }}">@icon('chevron-left', ['mr' => 0]) {{ $breadcrumb['label'] }}</a>
    @endforeach
</nav>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <p class="text-muted mb-1">{{ $guide['kind'] }}</p>
        <h2 class="mb-2">{{ $guide['title'] }}</h2>
        <span class="text-muted">@if($guide['kind'] === 'Level')@icon('circle', ['filled' => true, 'classes' => 'color-'.lastword($selected->name)])@endif{{ $guide['count'] }} {{ Str::plural('piece', $guide['count']) }}</span>
    </div>
    <a class="btn btn-secondary explore-view-all" href="{{ \App\Services\WebApp\ExploreCatalogue::url($guide['params'], $selectionLabel) }}">View all {{ $guide['count'] }} {{ Str::plural('piece', $guide['count']) }} @icon('arrow-right', ['mr' => 0])</a>
</div>
@if(!$selected && !$selectedTag && !request()->filled('mood') && (request()->filled('composers') || request()->filled('country')))
<p class="mb-4"><a href="{{ route('webapp.composers.index', array_filter(['sort' => 'name', 'composers' => request('composers'), 'country' => request('country')])) }}">Browse these composers A–Z @icon('arrow-right', ['mr' => 0])</a></p>
@endif
@if($choices->isNotEmpty())
<h5 class="mb-3">{{ $guide['heading'] }}</h5>
<div class="d-flex flex-column gap-3 mb-4">
@foreach($choices as $choice)
    <details class="explore-mood border rounded-sm">
        <summary>
            @if(!empty($choice['image']))
                <img src="{{ $choice['image'] }}" alt="" class="explore-artwork" width="56" height="56" loading="lazy">
            @else
                <span class="explore-icon bg-light rounded-circle">@icon($choice['icon'], ['mr' => 0, 'size' => 'lg', 'filled' => isset($choice['level']), 'classes' => isset($choice['level']) ? 'color-'.lastword($choice['level']) : ''])</span>
            @endif
            <span class="explore-copy"><strong>{{ $choice['label'] }}</strong><small class="d-block text-muted">{{ $choice['description'] ?: $choice['count'].' '.Str::plural('piece', $choice['count']) }}</small></span>
            @icon('chevron-right', ['mr' => 0, 'classes' => 'explore-chevron'])
        </summary>
        <div class="explore-mood-options">
            @if($choice['count'])
                @include('webapp.explore.link', ['href' => route('webapp.explore', $choice['params']), 'label' => 'Explore further', 'description' => 'Keep narrowing your selection', 'icon' => 'compass'])
                @include('webapp.explore.choice-actions', ['choice' => $choice, 'choiceLabel' => $selectionLabel.' · '.$choice['label']])
            @else
                <p class="text-muted py-3 mb-0">No pieces match this combination yet. Try another choice.</p>
            @endif
        </div>
    </details>
@endforeach
</div>
@elseif(!$guide['count'])
<p class="text-muted mb-4">No pieces match this combination yet. Follow the path above to try another choice.</p>
@endif
@if(!$selectedTag && ($techniques->count() > 1 || $lengths->count() > 1 || $periods->count() > 1))
<h5 class="mb-3">{{ $selected ? 'Other ways into this level' : 'Other ways to explore' }}</h5>
@if($techniques->count() > 1)
<details class="explore-mood border rounded-sm mb-3">
    <summary>@icon('hand', ['mr' => 0, 'size' => 'lg'])<span class="explore-copy"><strong>Technique</strong><small class="d-block text-muted">Hands, patterns & more</small></span>@icon('chevron-right', ['mr' => 0, 'classes' => 'explore-chevron'])</summary>
    <div class="explore-mood-options">
    @foreach($techniques as $tag)
        @include('webapp.explore.link', ['href' => \App\Services\WebApp\ExploreCatalogue::url($guide['params'] + ['tag' => $tag->id], $selectionLabel.' · '.ucfirst($tag->name)), 'label' => ucfirst($tag->name), 'matchingCount' => $tag->matching_pieces_count])
    @endforeach
    </div>
</details>
@endif
@if($lengths->count() > 1)
<details class="explore-mood border rounded-sm mb-3">
    <summary>@icon('clock', ['mr' => 0, 'size' => 'lg'])<span class="explore-copy"><strong>Length</strong><small class="d-block text-muted">Short, medium & long</small></span>@icon('chevron-right', ['mr' => 0, 'classes' => 'explore-chevron'])</summary>
    <div class="explore-mood-options">
    @foreach($lengths as $length)
        @include('webapp.explore.link', ['href' => \App\Services\WebApp\ExploreCatalogue::url($guide['params'] + ['length' => $length->name], $selectionLabel.' · '.ucfirst($length->name)), 'label' => ucfirst($length->name), 'matchingCount' => $length->matching_pieces_count])
    @endforeach
    </div>
</details>
@endif
@if($periods->count() > 1)
<details class="explore-mood border rounded-sm mb-3">
    <summary>@icon('layers', ['mr' => 0, 'size' => 'lg'])<span class="explore-copy"><strong>Periods</strong><small class="d-block text-muted">Explore by musical era</small></span>@icon('chevron-right', ['mr' => 0, 'classes' => 'explore-chevron'])</summary>
    <div class="explore-mood-options">
    @foreach($periods as $period)
        @include('webapp.explore.link', ['href' => \App\Services\WebApp\ExploreCatalogue::url($guide['params'] + ['tag' => $period->id], $selectionLabel.' · '.ucfirst($period->name)), 'label' => ucfirst($period->name), 'matchingCount' => $period->matching_pieces_count])
    @endforeach
    </div>
</details>
@endif
@endif
@php($highlightsLabel = [
    'Level' => 'Past highlights at this level',
    'Mood' => 'Past highlights with this mood',
    'Technique' => 'Past highlights with this technique',
    'Composers' => 'Past highlights by these composers',
    'Periods & Styles' => 'Past highlights from this period/style',
][$guide['kind']])
@include('webapp.explore.link', ['href' => route('webapp.highlights', ['explore' => $guide['params']]), 'label' => $highlightsLabel, 'icon' => 'clock', 'classes' => 'rounded-sm'])
