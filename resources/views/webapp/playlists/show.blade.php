@extends('webapp.layouts.app', ['title' => "$playlist->name - Piano Playlist"])

@section('content')
<div class="text-center mb-3">
    <div class="position-relative mb-3">
        @include('webapp.components.back')
        <h3 class="m-0">{{$playlist->name}}</h3>
    </div>
    <p class="px-2">{{$playlist->description}}</p>
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
