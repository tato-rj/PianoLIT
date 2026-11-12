@php
    $searchTitle = 'Piano Piece Catalogue'.($pieces->currentPage() > 1 ? ' | Page '.$pieces->currentPage() : '');
    $searchDescription = 'Browse PianoLIT’s piano repertoire by title. Open a piece for its composer, difficulty, and available practice resources.';
    $searchEntity = ['@type' => 'CollectionPage', 'url' => $canonical, 'name' => $searchTitle, 'description' => $searchDescription];
@endphp
@extends('webapp.layouts.app', ['title' => $searchTitle])

@section('page-metadata')
    @include('components.seo.resource-head', ['siteName' => 'PianoLIT', 'searchImage' => asset('images/webapp/thumbnail.jpg')])
@endsection

@section('content')
@component('webapp.layouts.header', ['title' => 'Piano Piece Catalogue', 'subtitle' => $searchDescription, 'pageHeading' => true])
    <nav class="d-flex flex-wrap justify-content-center gap-3" aria-label="Repertoire navigation">
        <a href="{{ route('webapp.search.results') }}">Search piano pieces</a>
        <a href="{{ route('webapp.composers.index') }}">Browse composers</a>
        <a href="{{ route('webapp.playlists') }}">Browse collections</a>
    </nav>
@endcomponent

@if($pieces->isNotEmpty())
    <p class="text-muted small">Showing {{ $pieces->firstItem() }}–{{ $pieces->lastItem() }} of {{ number_format($pieces->total()) }} pieces</p>
    <ul class="list-unstyled border-top">
        @foreach($pieces as $piece)
            <li class="py-3 border-bottom">
                <a class="d-block fw-semibold" href="{{ route('webapp.pieces.show', $piece) }}">{{ $piece->medium_name }}</a>
                <span class="small text-muted">{{ $piece->attribution }}{{ $piece->composer->name }}@if($piece->extended_level_name) · {{ ucfirst($piece->extended_level_name) }}@endif</span>
            </li>
        @endforeach
    </ul>
    {{ $pieces->onEachSide(1)->links() }}
@else
    <p>No pieces on this page.</p>
    <a href="{{ route('webapp.pieces.index') }}">Back to the catalogue</a>
@endif
@endsection
