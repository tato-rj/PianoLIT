@extends('webapp.layouts.app')

@section('content')
<div class="playlist-brand">@include('webapp.layouts.header')</div>
<section class="playlist-page" data-playlist-page>
    <header class="playlist-heading">
        @include('webapp.components.back')
        <p class="playlist-updated text-muted">last updated on {{$folder->updated_at->toFormattedDateString()}}</p>
        <h1 class="h3">@icon('folder-open', ['mr' => 0])<span>{{$folder->name}}</span></h1>
        <span class="badge rounded-pill alert-blue" data-playlist-count>{{$folder->favorites->count()}} {{str_plural('piece', $folder->favorites->count())}}</span>
        <div class="playlist-options dropdown">
            <button class="btn btn-secondary btn-sm playlist-options__toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Folder options">@icon('ellipsis', ['mr' => 0])</button>
            <div class="dropdown-menu dropdown-menu-end">
                <button class="dropdown-item" type="button" data-bs-toggle="modal" data-bs-target="#edit-folder-{{$folder->id}}">Edit folder</button>
                <button class="dropdown-item text-danger" type="button" data-bs-toggle="modal" data-bs-target="#delete-folder-{{$folder->id}}">Delete folder</button>
            </div>
        </div>
    </header>
    @include('webapp.user.my-pieces.favorites.folders.pdf', ['classes' => 'playlist-escore'])
    @include('webapp.playlist.tracks', ['pieces' => $folder->favorites->pluck('piece'), 'favorites' => $folder->favorites])
</section>
@include('webapp.user.my-pieces.favorites.edit')
@include('webapp.user.my-pieces.favorites.delete')
@endsection

@push('scripts')
<script src="{{mix('js/views/playlist-player.js')}}"></script>
@endpush
