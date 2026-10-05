@php
    $country = $composer->country;
    $works = $composerWorks->get($composer->id, collect())->flatMap(function ($work) {
        return [$work->name, $work->collection_name];
    })->filter()->unique()->implode(' ');
@endphp
<div class="col-xl-3 col-md-6 col-12 composer-card"
    data-composer-name="{{ $composer->last_name }}"
    data-composer-search="{{ $composer->name }} {{ $country->name ?? '' }} {{ $works }}"
    data-composer-popular="{{ $composer->is_famous ? 'true' : 'false' }}"
    data-composer-created="{{ $composer->created_at ? $composer->created_at->getTimestamp() : 0 }}"
    data-composer-pieces="{{ $composer->pieces_count }}">
    <a href="{{ route('webapp.composers.show', $composer) }}" class="link-none border rounded hover-shadow p-3 h-100 d-flex align-items-center gap-3">
        <img src="{{ $composer->cover_image }}" alt="" class="composer-portrait rounded-circle flex-shrink-0" loading="lazy">
        <div class="composer-copy flex-grow-1">
            <h6 class="mb-1">{{ $composer->name }}</h6>
            @if($country)
            <div class="small text-muted">@flag(['code' => $country->flag_code]){{ $country->name }}</div>
            @endif
        </div>
        @icon('chevron-right', ['color' => 'muted', 'mr' => 0, 'classes' => 'flex-shrink-0'])
    </a>
</div>
