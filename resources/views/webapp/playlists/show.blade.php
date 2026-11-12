@php
    $searchTitle = $playlist->name.' - Piano Playlist';
    $summary = trim($playlist->description ?: $playlist->subtitle ?: '');
    $searchDescription = Str::limit(trim($playlist->name.' includes '.$totalPieceCount.' piano '.Str::plural('piece', $totalPieceCount).'. '.strip_tags($summary)), 160);
    if ($canonical) {
        $visiblePieces = $guestCap === null ? $pieces : $pieces->take($guestCap);
        $items = $visiblePieces->values()->map(fn ($piece, $index) => [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $piece->medium_name,
            'url' => route('webapp.pieces.show', $piece),
        ]);
        $searchEntity = [
            '@type' => 'CollectionPage',
            'url' => $canonical,
            'name' => $playlist->name,
            'description' => $searchDescription,
            'mainEntity' => ['@type' => 'ItemList', 'itemListElement' => $items],
        ];
    }
@endphp
@extends('webapp.layouts.app', ['title' => $searchTitle])

@section('page-metadata')
    @include('components.seo.resource-head', ['siteName' => 'PianoLIT', 'searchImage' => $playlist->cover_image ?: asset('images/webapp/collections/featured.webp')])
@endsection

@section('content')
<div class="text-center mb-3">
    <div class="position-relative mb-3">
        @include('webapp.components.back')
        <h1 class="h3 m-0">{{ $playlist->name }}</h1>
    </div>
    @if($summary)<p class="px-2">{{ $summary }}</p>@endif
</div>
<section class="playlist-page playlist-page--collection" data-playlist-page>
    <div class="text-center">
        <span class="badge rounded-pill alert-blue" data-collection-count>{{$totalPieceCount}} {{str_plural('piece', $totalPieceCount)}}</span>
        @if($collectionCap !== null && $totalPieceCount > $pieces->count())
            <p class="small text-muted mt-2 mb-0" data-collection-preview-count>Showing {{ $pieces->count() }} of {{ $totalPieceCount }} pieces</p>
        @endif
    </div>
    @include('webapp.playlist.escore', ['escorePieces' => $pieces, 'escoreName' => $playlist->name, 'escoreUrl' => route('webapp.playlists.pdf', $playlist)])
    @include('webapp.playlist.tracks')
    @if($collectionCap !== null && $totalPieceCount > $pieces->count())
        <div class="text-center py-4" data-collection-limit>
            <p>You can view the first {{ $collectionCap }} pieces in this collection.</p>
            @include('webapp.components.access-action', ['accessFeature' => 'collections'])
        </div>
    @endif
</section>
@endsection

@push('scripts')
<script src="{{mix('js/views/video-moments.js')}}"></script>
<script src="{{mix('js/views/playlist-moments.js')}}"></script>
<script src="{{mix('js/views/playlist-player.js')}}"></script>
@endpush
