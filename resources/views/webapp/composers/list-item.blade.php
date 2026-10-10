@php
    $country = $composer->country;
    $birthYear = $composer->unknownBirthday() ?: $composer->born_in;
    $deathYear = $composer->unknownDeathday() ?: $composer->died_in;
    $works = $composerWorks->get($composer->id, collect())->flatMap(function ($work) {
        return [$work->name, $work->collection_name];
    })->filter()->unique()->implode(' ');
@endphp
<div class="col-md-6 col-12 composer-card"
    data-composer-name="{{ $composer->last_name }}"
    data-composer-regions="{{ $country->continent ?? '' }}{{ $country && $country->matchesLatinAmericaSearch() ? '|Latin America' : '' }}"
    data-composer-period="{{ strtolower($composer->period ?? '') }}"
    data-composer-continent="{{ strtolower($country->continent ?? '') }}"
    data-composer-gender="{{ strtolower($composer->gender ?? '') }}"
    data-composer-search="{{ $composer->name }} {{ $country->name ?? '' }} {{ $country->continent ?? '' }} {{ $works }}{{ $country && $country->matchesLatinAmericaSearch() ? ' Latin America' : '' }}"
    data-composer-popular="{{ $composer->is_famous ? 'true' : 'false' }}"
    data-composer-created="{{ $composer->created_at ? $composer->created_at->getTimestamp() : 0 }}"
    data-composer-pieces="{{ $composer->pieces_count }}">
    <a href="{{ route('webapp.composers.show', $composer) }}" class="composer-card-link link-none">
        <img src="{{ $composer->cover_image }}" alt="" class="composer-portrait flex-shrink-0" loading="lazy" width="104" height="116">
        <div class="composer-copy flex-grow-1">
            <div class="composer-card-heading">
                <h6 class="composer-card-name">{{ $composer->name }}</h6>
            </div>
            @if($country)
            <div class="composer-card-country">@flag(['code' => $country->flag_code]){{ $country->name }}</div>
            @endif
            @if($birthYear || $deathYear)
            <div class="composer-card-years">@if($birthYear && $deathYear){{ $birthYear }} – {{ $deathYear }}@elseif($birthYear)Born {{ $birthYear }}@else Died {{ $deathYear }}@endif</div>
            @endif
            <div class="composer-card-details">
                @if($composer->period)
                <span class="composer-period" data-period="{{ strtolower($composer->period) }}">{{ $composer->period }}</span>
                @endif
                <span class="composer-piece-count">{{ $composer->pieces_count }} {{ $composer->pieces_count == 1 ? 'piece' : 'pieces' }}</span>
            </div>
        </div>
        @icon('chevron-right', ['mr' => 0, 'classes' => 'composer-card-chevron flex-shrink-0'])
    </a>
</div>
