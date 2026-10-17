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
        <span class="badge rounded-pill alert-blue">{{$pieces->count()}} {{str_plural('piece', $pieces->count())}}</span>
    </div>
    @if($collectionCap !== 0)
    @include('webapp.playlist.escore', ['escorePieces' => $pieces, 'escoreName' => $playlist->name, 'escoreUrl' => route('webapp.playlists.pdf', $playlist)])
    @include('webapp.playlist.tracks')
    @endif
    @if($collectionCap !== null)
        <div class="text-center py-4" data-collection-limit>
            <p>{{ $collectionCap === 0 ? (auth('web')->guest() ? 'Sign up to explore collections.' : 'Collections are currently locked.') : 'You can view the first 3 pieces in this collection.' }}</p>
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
