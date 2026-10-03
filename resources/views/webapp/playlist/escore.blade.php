@php($scoreCount = $escorePieces->filter(function ($item) { return $item->score_path && $item->is_public_domain && $item->hasWebMediaAccess(auth('web')->user()); })->count())
<div class="text-center playlist-escore-wrap"><button class="btn btn-secondary playlist-escore" type="button" data-bs-toggle="modal" data-bs-target="#playlist-escore-modal">@icon('tablet', ['mr' => 0])Create eScore</button></div>
@auth('web')
@include('webapp.playlist.escore-form', ['escoreModalId' => 'playlist-escore-modal', 'escoreId' => 'collection-escore', 'escoreDescription' => strip_tags($playlist->description)])
@else
@component('components.modal', ['id' => 'playlist-escore-modal', 'header' => 'Create eScore'])
@slot('body')<p>Sign in to create an eScore from this collection.</p><a class="btn btn-primary" href="{{ route('login') }}">Sign in</a>@endslot
@endcomponent
@endauth
