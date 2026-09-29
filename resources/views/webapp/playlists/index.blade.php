@extends('webapp.layouts.app', ['title' => 'Collections'])

@section('content')
@component('webapp.layouts.header', ['title' => 'Collections', 'subtitle' => 'Find something beautiful to play.'])
@endcomponent

<div class="collections-page" id="collections-page">
    @if(count($books))
    <a class="collections-intro" href="#pianolit-path">
        <span class="collections-intro__art" aria-hidden="true">
            <img src="{{ asset('images/webapp/collections/book-1.webp') }}" alt="" width="120" height="120">
            <span>1</span>
        </span>
        <span class="collections-intro__copy">
            <span class="collections-eyebrow">THE PIANOLIT PATH</span>
            <strong>A little direction. A world of music.</strong>
            <span>Meet our upcoming Piano Solos series.</span>
        </span>
        <span class="collections-intro__action">Discover the series @icon('arrow-right', ['mr' => 0])</span>
    </a>
    @endif

    @if($featured)
    <section class="collections-feature" aria-labelledby="featured-heading">
        <div class="collections-feature__copy">
            <span class="collections-eyebrow">FEATURED COLLECTION</span>
            <h1 id="featured-heading">{{ $featured['playlist']->name }}</h1>
            <p>{{ $featured['playlist']->subtitle ?: 'Discover something beautiful in the piano repertoire.' }}</p>
            <span class="collections-meta">{{ $featured['playlist']->pieces_count }} pieces</span>
            <a class="btn btn-primary" href="{{ route('webapp.playlists.show', $featured['playlist']) }}">Explore collection @icon('arrow-right', ['mr' => 0])</a>
        </div>
        <img class="collections-feature__art" src="{{ asset('images/webapp/collections/featured.webp') }}" alt="Abstract piano keys flowing through a moonlit landscape" width="1536" height="1024" fetchpriority="high">
    </section>
    @endif

    @if(count($books))
    <section class="collections-section" id="pianolit-path" aria-labelledby="path-heading">
        <div class="collections-section__heading">
            <div>
                <h2 id="path-heading">The PianoLit path</h2>
                <p>Grow through our Piano Solos series, one book at a time.</p>
            </div>
            <div class="collections-book-controls" data-book-controls hidden>
                <button type="button" data-book-previous aria-label="Previous books" aria-controls="piano-solos-books" disabled>@icon('arrow-left', ['mr' => 0])</button>
                <button type="button" data-book-next aria-label="Next books" aria-controls="piano-solos-books">@icon('arrow-right', ['mr' => 0])</button>
            </div>
        </div>
        <div class="collections-books" id="piano-solos-books" tabindex="0" role="region" aria-label="Piano Solos books, scroll to explore all eight">
            @foreach($books as $book)
            <article class="collections-book">
                <div class="collections-book__art">
                    <img src="{{ asset('images/webapp/collections/'.$book['image'].'.webp') }}" alt="" width="900" height="600" loading="lazy">
                    <span class="collections-book__series">PIANO SOLOS</span>
                    <span class="collections-book__number" aria-hidden="true">{{ $book['number'] }}</span>
                </div>
                <div class="collections-book__copy">
                    <h3>Piano Solos · Book {{ $book['number'] }}</h3>
                    <p>{{ $book['level'] }}</p>
                    <span class="collections-coming-soon">Coming soon</span>
                </div>
            </article>
            @endforeach
        </div>
        <p class="collections-path-note">Selected repertoire, with guidance on what each piece develops.</p>
    </section>
    @endif

    @if($inspiration->isNotEmpty())
    <section class="collections-section" aria-labelledby="inspiration-heading">
        <div class="collections-section__heading">
            <h2 id="inspiration-heading">What would you like to play next?</h2>
            <a href="#browse-collections">View all collections @icon('arrow-right', ['mr' => 0])</a>
        </div>
        <div class="collections-inspiration">
            @foreach($inspiration as $card)
                @include('webapp.playlists.collection-card', ['expanded' => true])
            @endforeach
        </div>
    </section>
    @endif

    <section class="collections-section" id="browse-collections" aria-labelledby="browse-heading">
        <div class="collections-section__heading"><h2 id="browse-heading">Browse collections</h2></div>
        @if($playlists->isNotEmpty())
            @if($categories->isNotEmpty())
            <div class="collections-filters" role="group" aria-label="Filter collections" hidden>
                <button type="button" data-collection-filter="all" aria-pressed="true" aria-controls="collections-grid">All</button>
                @foreach($categories as $key => $label)
                <button type="button" data-collection-filter="{{ $key }}" aria-pressed="false" aria-controls="collections-grid">{{ $label }}</button>
                @endforeach
            </div>
            @endif
            <div class="collections-grid" id="collections-grid">
                @foreach($playlists as $card)
                    @include('webapp.playlists.collection-card', ['expanded' => false])
                @endforeach
            </div>
            <p class="sr-only" data-collection-status role="status" aria-live="polite"></p>
        @else
            <div class="collections-empty">
                <h3>More music is on its way</h3>
                <p>Our collections are being prepared. Explore the repertoire in the meantime.</p>
                <a href="{{ route('webapp.explore') }}" class="btn btn-primary">Explore pieces @icon('arrow-right', ['mr' => 0])</a>
            </div>
        @endif
    </section>
</div>
@endsection

@push('scripts')
<script src="{{ mix('js/views/collections.js') }}" defer></script>
@endpush
