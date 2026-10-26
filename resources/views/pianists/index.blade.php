@extends('layouts.app', [
    'title' => 'Great Pianists | PianoLIT Learn', 'bodyClass' => 'learn-home pianist-page',
    'shareable' => [
        'keywords' => 'pianists,classical music,piano recordings,biographies', 'title' => 'Great Pianists',
        'description' => 'Meet remarkable pianists, explore their stories, and discover their recordings.',
        'thumbnail' => asset('images/misc/thumbnails/pianists.jpg'),
        'created_at' => carbon('16-09-2019'), 'updated_at' => carbon('16-09-2019'),
    ],
])

@section('content')
@php
    $countryChoices = ['' => 'All countries'];
    foreach ($countries as $country) $countryChoices[$country->id] = $country->name;
@endphp
<div class="container py-4 pb-5">
    @include('components.breadcrumb', ['items' => [
        ['label' => 'Home', 'url' => route('home')],
        ['label' => 'Learn', 'url' => route('learn.home')],
        ['label' => 'Great Pianists'],
    ]])
    @component('components.hub-header', ['heading' => 'Great Pianists', 'preview' => false])
        Remarkable artists. A lifetime of music.<br class="d-none d-md-block"> Get to know the pianists, then discover their recordings.
    @endcomponent

    @component('components.editorial-layout')
        <form method="GET" action="{{ route('resources.pianists.index') }}" class="row g-3 align-items-end mb-4" aria-label="Find a pianist">
            <div class="col-md">
                @input(['bag' => 'default', 'name' => 'search', 'id' => 'search-pianist', 'label' => 'Find a pianist', 'value' => $filters['search'], 'placeholder' => 'Name, country or nationality', 'required' => false, 'grid' => 'mb-0'])
            </div>
            <div class="col-sm-7 col-md-auto">
                @select(['bag' => 'default', 'name' => 'country', 'id' => 'pianist-country', 'label' => 'Country', 'choices' => $countryChoices, 'value' => $filters['country'], 'required' => false, 'grid' => 'mb-0'])
            </div>
            <div class="col-sm-auto d-flex gap-2">
                <button type="submit" class="btn btn-primary">@icon('search')Search</button>
                @if($filters['search'] || $filters['country'])<a class="btn btn-secondary" href="{{ route('resources.pianists.index') }}">Clear</a>@endif
            </div>
        </form>
        <section aria-labelledby="pianists-directory-title">
            <div class="d-flex flex-wrap justify-content-between align-items-baseline gap-2 mb-4">
                <h2 class="h5 mb-0" id="pianists-directory-title">Meet the artists</h2>
                <p class="small text-muted mb-0">{{ $pianists->isNotEmpty() ? 'Showing '.$pianists->firstItem().'–'.$pianists->lastItem().' of '.number_format($pianists->total()) : number_format($pianists->total()) }} {{ Str::plural('pianist', $pianists->total()) }}</p>
            </div>
            @if($pianists->isNotEmpty())
                <div class="row g-4" id="pianists">
                    @foreach($pianists as $pianist)
                        <div class="col-sm-6 col-xl-4">@include('pianists.card', ['cardId' => 'pianist-'.$pianist->id])</div>
                    @endforeach
                </div>
                @if($pianists->hasPages())<div class="mt-5">{{ $pianists->onEachSide(1)->links() }}</div>@endif
            @else
                <div class="py-4">
                    <h3 class="h5">{{ $filters['search'] || $filters['country'] ? 'No pianists match your search' : 'New artist profiles are on their way' }}</h3>
                    <p class="text-muted">{{ $filters['search'] || $filters['country'] ? 'Try another name or country, or browse all the artists.' : 'Explore our other music resources while you wait.' }}</p>
                    <a class="btn btn-secondary" href="{{ $filters['search'] || $filters['country'] ? route('resources.pianists.index') : route('learn.home') }}">{{ $filters['search'] || $filters['country'] ? 'View all pianists' : 'Explore Learn' }} @icon('arrow-right', ['mr' => 0, 'ml' => 2])</a>
                </div>
            @endif
        </section>
        @slot('sidebar')
            @include('components.display.ads', ['ads' => ['crashcourse', 'ebook', 'escore'], 'variant' => 'cards'])
        @endslot
    @endcomponent
</div>
@endsection
