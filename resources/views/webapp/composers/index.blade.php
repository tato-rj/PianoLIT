@extends('webapp.layouts.app', ['title' => 'Composers'])

@push('header')
<link rel="preload" href="{{ asset('css/vendor/flag-icon/flag-icon.min.css') }}" as="style">
<link href="{{ asset('css/vendor/flag-icon/flag-icon.min.css') }}" rel="stylesheet">
@endpush

@section('content')
@include('webapp.layouts.header', ['title' => request('gender') === 'female' ? 'Women composers' : 'Composers', 'subtitle' => 'Explore our list of composers'])

@if(request()->filled('gender') || request()->filled('country'))
<p><a href="{{ route('webapp.composers.index') }}">@icon('arrow-left') All composers</a>@if(request()->filled('country') && $composers->first())<span class="text-muted ms-3">{{ optional($composers->first()->country)->name }}</span>@endif</p>
@endif
<section id="composers-directory" aria-label="Composer directory">
    <div class="d-flex align-items-center gap-3 mb-4">
        <div class="flex-grow-1 composer-search">
            @include('webapp.search.form', ['directorySearch' => true, 'searchLabel' => 'Search composers, countries, or works', 'searchPlaceholder' => 'Search composers, countries, or works...'])
        </div>
        <div class="dropdown" data-composer-controls hidden>
            <button class="btn btn-secondary d-flex align-items-center gap-2" type="button" id="composer-sort" aria-label="Sort composers" data-bs-toggle="dropdown" aria-expanded="false">
                @icon('arrow-down-up', ['mr' => 0])<span class="d-none d-md-inline">Sort</span>
            </button>
            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="composer-sort" role="group" aria-label="Sort composers">
                <button class="dropdown-item active" type="button" data-composer-sort="pieces" aria-pressed="true">Most pieces</button>
                <button class="dropdown-item" type="button" data-composer-sort="name" aria-pressed="false">Last name A–Z</button>
                <button class="dropdown-item" type="button" data-composer-sort="recent" aria-pressed="false">Recently added</button>
            </div>
        </div>
    </div>

    <div class="d-flex flex-column flex-xl-row align-items-xl-center gap-3 mb-4" data-composer-controls hidden>
        <div class="d-flex gap-2 overflow-x-auto flex-shrink-0 pb-1" role="group" aria-label="Filter composers">
            @foreach(['all' => 'All composers', 'popular' => 'Popular', 'recent' => 'Recently added'] as $filter => $label)
            <button class="btn btn-secondary btn-sm composer-pill text-nowrap {{ $loop->first ? 'active' : '' }}" type="button" data-composer-filter="{{ $filter }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}" aria-controls="composers-list">{{ $label }}</button>
            @endforeach
        </div>
        <div class="composer-alphabet d-flex gap-1 overflow-x-auto pb-1" role="group" aria-label="Filter by last name">
            <button class="btn btn-secondary btn-sm composer-pill active" type="button" data-composer-letter="all" aria-pressed="true" aria-controls="composers-list">All</button>
            @foreach(range('A', 'Z') as $letter)
            <button class="btn btn-secondary btn-sm composer-pill" type="button" data-composer-letter="{{ strtolower($letter) }}" aria-label="Last name starting with {{ $letter }}" aria-pressed="false" aria-controls="composers-list">{{ $letter }}</button>
            @endforeach
        </div>
    </div>

    <div class="row g-3" id="composers-list">
        @foreach($composers as $composer)
            @include('webapp.composers.list-item')
        @endforeach
    </div>
    <div class="border rounded p-4 text-center" data-composer-empty @if($composers->isNotEmpty()) hidden @endif>
        <p class="text-muted mb-3">No composers found. Try another search or reset the filters.</p>
        <button class="btn btn-secondary" type="button" data-composer-reset hidden>Reset filters</button>
    </div>
    <p class="visually-hidden" role="status" aria-live="polite" data-composer-status></p>
</section>
@endsection

@push('scripts')
<script src="{{ mix('js/views/composers.js') }}"></script>
@endpush
