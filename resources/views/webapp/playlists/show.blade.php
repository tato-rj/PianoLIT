@extends('webapp.layouts.app')

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
        <span class="badge rounded-pill alert-blue">{{$pieces->count()}} {{str_plural('piece', $pieces->count())}}</span>
    </div>
    @include('webapp.playlist.escore', ['escorePieces' => $pieces, 'escoreName' => $playlist->name, 'escoreUrl' => route('webapp.playlists.pdf', $playlist)])
    @include('webapp.playlist.tracks')
</section>
@endsection

@push('scripts')
<script src="{{mix('js/views/playlist-player.js')}}"></script>
@endpush
