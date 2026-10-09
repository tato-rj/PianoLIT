<nav class="d-flex {{ empty($breadcrumbs) ? 'd-lg-none' : '' }} flex-wrap align-items-center gap-2 mb-3 mt-4 mt-lg-0" aria-label="Explore path">
    <a class="d-lg-none" href="{{ route('webapp.explore') }}">@icon('chevron-left', ['mr' => 0]) Explore</a>
    @foreach($breadcrumbs as $breadcrumb)
        @icon('chevron-right', ['mr' => 0, 'classes' => $loop->first ? 'd-lg-none' : ''])<a href="{{ route('webapp.explore', $breadcrumb['params']) }}">{{ $breadcrumb['label'] }}</a>
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
@else
<div class="border rounded mb-4 p-2">
    @if($guide['count'])
        @include('webapp.explore.choice-actions', ['choice' => $guide, 'choiceLabel' => $selectionLabel])
    @else
        <p class="text-muted p-3 mb-0">No pieces match this combination yet. Follow the path above to try another choice.</p>
    @endif
</div>
@endif
@if(!$selectedTag)
<h5 class="mb-3">{{ $selected ? 'Other ways into this level' : 'Other ways to explore' }}</h5>
<details class="explore-mood border rounded-sm mb-3" @if($selected && request()->filled('mood')) open @endif>
    <summary>@icon('hand', ['mr' => 0, 'size' => 'lg'])<span class="explore-copy"><strong>Technique</strong><small class="d-block text-muted">Hands, patterns & more</small></span>@icon('chevron-right', ['mr' => 0, 'classes' => 'explore-chevron'])</summary>
    <div class="explore-mood-options">
    @forelse($techniques as $tag)
        @include('webapp.explore.link', ['href' => route('webapp.explore', $guide['params'] + ['tag' => $tag->id]), 'label' => ucfirst($tag->name)])
    @empty<p class="text-muted py-3">No techniques match this selection.</p>@endforelse
    </div>
</details>
@endif
@if($selected && count($guide['params']) === 1)
@include('webapp.explore.link', ['href' => route('webapp.highlights', ['filters' => [json_encode([$selected->name])]]), 'label' => 'Past free picks at this level', 'icon' => 'clock', 'classes' => 'border rounded'])
@endif
