@php($levelParams = ['level' => $selected->name])
<a class="d-inline-flex align-items-center gap-2 mb-3 mt-4 mt-lg-0" href="{{ route('webapp.explore') }}">@icon('chevron-left', ['mr' => 0]) Explore</a>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <p class="text-muted mb-1">Level</p>
        <h2 class="mb-2">{{ ucwords($selected->name) }}</h2>
        <span class="text-muted">@icon('circle', ['filled' => true, 'classes' => 'color-'.lastword($selected->name)]){{ $selected->pieces_count }} {{ Str::plural('piece', $selected->pieces_count) }}</span>
    </div>
    <a class="btn btn-secondary explore-view-all" href="{{ \App\Services\WebApp\ExploreCatalogue::url($levelParams, ucwords($selected->name)) }}">View all {{ $selected->pieces_count }} {{ Str::plural('piece', $selected->pieces_count) }} @icon('arrow-right', ['mr' => 0])</a>
</div>
<h4 class="mb-3">By character</h4>
<div class="d-flex flex-column gap-3 mb-4">
@foreach($moods as $key => $mood)
    <details class="explore-mood border rounded" @if(!$mood['count']) data-empty @endif>
        <summary>
            <span class="explore-icon bg-light rounded-circle">@icon($mood['icon'], ['mr' => 0, 'size' => 'lg'])</span>
            <span class="explore-copy"><strong>{{ $mood['label'] }}</strong><small class="d-block text-muted">{{ $mood['description'] }}</small></span>
            @icon('chevron-right', ['mr' => 0, 'classes' => 'explore-chevron'])
        </summary>
        <div class="explore-mood-options">
            @if($mood['count'])
                @include('webapp.explore.link', ['href' => \App\Services\WebApp\ExploreCatalogue::url($levelParams + ['mood' => $key, 'short' => 1], ucwords($selected->name).' · '.$mood['label'].' · Short pieces'), 'label' => 'Short pieces', 'description' => 'A smaller time commitment', 'icon' => 'clock'])
                @include('webapp.explore.link', ['href' => \App\Services\WebApp\ExploreCatalogue::url($levelParams + ['mood' => $key], ucwords($selected->name).' · '.$mood['label']), 'label' => 'All '.strtolower($mood['label']).' pieces', 'description' => $mood['count'].' '.Str::plural('piece', $mood['count']).' to explore', 'icon' => 'list'])
                @if($mood['example'])
                <a class="explore-link text-primary" href="{{ route('webapp.pieces.show', $mood['example']) }}">@icon('circle-play', ['mr' => 0, 'size' => 'lg'])<span class="explore-copy">Hear an example<small class="d-block text-muted">Open the piece player</small></span>@icon('arrow-right', ['mr' => 0])</a>
                @endif
            @else
                <p class="text-muted py-3 mb-0">No pieces with this character at this level yet. Try another mood or level.</p>
            @endif
        </div>
    </details>
@endforeach
</div>
<h4 class="mb-3">Other ways into this level</h4>
<details class="explore-mood border rounded mb-3">
    <summary>@icon('hand', ['mr' => 0, 'size' => 'lg'])<span class="explore-copy"><strong>Technique</strong><small class="d-block text-muted">Hands, patterns & more</small></span>@icon('chevron-right', ['mr' => 0, 'classes' => 'explore-chevron'])</summary>
    <div class="explore-mood-options">
    @forelse($tags->where('type', 'technique') as $tag)
        @include('webapp.explore.link', ['href' => \App\Services\WebApp\ExploreCatalogue::url($levelParams + ['tag' => $tag->id], ucwords($selected->name).' · '.ucfirst($tag->name)), 'label' => ucfirst($tag->name)])
    @empty<p class="text-muted py-3">Technique collections are being prepared.</p>@endforelse
    </div>
</details>
@include('webapp.explore.link', ['href' => \App\Services\WebApp\ExploreCatalogue::url($levelParams + ['past' => 1], ucwords($selected->name).' · Past free picks'), 'label' => 'Past free picks at this level', 'icon' => 'clock', 'classes' => 'border rounded'])
