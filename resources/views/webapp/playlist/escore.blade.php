@php($canGenerateEscore = app(\App\Services\WebApp\AccessPolicy::class)->available('escores', auth('web')->user()))
<div class="text-center playlist-escore-wrap"><button class="btn btn-secondary playlist-escore" type="button" data-access-action="escore" data-bs-toggle="modal" data-bs-target="{{ $canGenerateEscore ? '#playlist-escore-modal' : app(\App\Services\WebApp\AccessPolicy::class)->promptTarget(auth('web')->user(), 'escore') }}">@icon('book-open-text', ['mr' => 0])Create eScore</button></div>
@if($canGenerateEscore)
@php($scoreCount = $escorePieces->filter(function ($item) { return $item->score_path && $item->is_public_domain && $item->hasWebMediaAccess(auth('web')->user(), 'exports'); })->count())
@include('webapp.playlist.escore-form', ['escoreModalId' => 'playlist-escore-modal', 'escoreId' => 'collection-escore', 'escoreDescription' => strip_tags($playlist->description), 'escoreCoverImage' => $playlist->cover_path ? $playlist->cover_image : null])
@endif
