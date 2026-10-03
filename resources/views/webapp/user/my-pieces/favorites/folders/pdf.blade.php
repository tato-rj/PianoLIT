<div class="text-center w-100"><button type="button" class="btn btn-secondary {{ $classes ?? null }}" data-bs-toggle="modal" data-bs-target="#generate-pdf-folder-{{ $folder->id }}">@icon('tablet', ['classes' => ''])Create eScore</button></div>
@php($escorePieces = $folder->favorites->pluck('piece'))
@php($scoreCount = $escorePieces->filter(function ($piece) { return $piece && $piece->score_path && $piece->is_public_domain && $piece->hasWebMediaAccess(auth('web')->user()); })->count())
@include('webapp.playlist.escore-form', ['escoreModalId' => 'generate-pdf-folder-'.$folder->id, 'escoreId' => 'folder-escore-'.$folder->id, 'escoreUrl' => route('webapp.users.favorites.folders.pdf', $folder), 'escoreName' => $folder->name, 'escoreDescription' => $folder->description])
