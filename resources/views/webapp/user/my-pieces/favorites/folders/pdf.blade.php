@php($canGenerateEscore = app(\App\Services\WebApp\AccessPolicy::class)->available('escores', auth('web')->user()))
<div class="text-center w-100"><button type="button" class="btn btn-secondary {{ $classes ?? null }}" data-access-action="escore" data-bs-toggle="modal" data-bs-target="{{ $canGenerateEscore ? '#generate-pdf-folder-'.$folder->id : app(\App\Services\WebApp\AccessPolicy::class)->promptTarget(auth('web')->user(), 'escore') }}">@icon('book-open-text', ['classes' => ''])Create eScore</button></div>
@if($canGenerateEscore)
@php($escorePieces = $folder->favorites->pluck('piece'))
@php($scoreCount = $escorePieces->filter(function ($piece) { return $piece && $piece->score_path && $piece->is_public_domain && $piece->hasWebMediaAccess(auth('web')->user(), 'exports'); })->count())
@include('webapp.playlist.escore-form', ['escoreModalId' => 'generate-pdf-folder-'.$folder->id, 'escoreId' => 'folder-escore-'.$folder->id, 'escoreUrl' => route('webapp.users.favorites.folders.pdf', $folder), 'escoreName' => $folder->name, 'escoreDescription' => $folder->description])
@endif
