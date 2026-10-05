@php($canGenerateEscore = auth('web')->check() && auth('web')->user()->hasActiveSubscription())
<div class="text-center playlist-escore-wrap"><button class="btn btn-secondary playlist-escore" type="button" data-bs-toggle="modal" data-bs-target="{{ $canGenerateEscore ? '#playlist-escore-modal' : '#piece-upgrade-modal' }}">@icon('tablet', ['mr' => 0])Create eScore</button></div>
@if($canGenerateEscore)
@php($scoreCount = $escorePieces->filter(function ($item) { return $item->score_path && $item->is_public_domain && $item->hasWebMediaAccess(auth('web')->user()); })->count())
@include('webapp.playlist.escore-form', ['escoreModalId' => 'playlist-escore-modal', 'escoreId' => 'collection-escore', 'escoreDescription' => strip_tags($playlist->description), 'escoreCoverImage' => $playlist->cover_path ? $playlist->cover_image : null])
@endif
