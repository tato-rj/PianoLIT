@php($canAddPiece = app(\App\Services\WebApp\FavoriteAccess::class)->canAdd(auth('web')->user(), $folder->favorites_count))
@php($is_favorited = $folder->piece_favorites_count > 0)
<button type="button" class="save-to-folder{{ $is_favorited ? ' is-saved' : '' }}"
    @if(!$canAddPiece && !$is_favorited)
    data-bs-toggle="modal" data-bs-target="{{ app(\App\Services\WebApp\AccessPolicy::class)->promptTarget(auth('web')->user(), 'favorites') }}"
    @else
    data-submit="favorite" data-url="{{ route('webapp.users.favorites.update', ['piece' => $piece, 'folder_id' => $folder->id]) }}"
    @endif
    aria-label="{{ $is_favorited ? 'Remove from' : 'Save to' }} {{ $folder->name }}" aria-pressed="{{ $is_favorited ? 'true' : 'false' }}">
    <span class="save-to-folder__icon" aria-hidden="true">@icon('folder-open', ['mr' => 0])</span>
    <span class="save-to-folder__copy">
        <strong>{{ $folder->name }}</strong>
        <small>{{ $folder->favorites_count }} {{ str_plural('piece', $folder->favorites_count) }}</small>
    </span>
    <span class="favorite-icons save-to-folder__status">
        @icon('circle-check', ['name' => 'saved', 'mr' => 0, 'if' => $is_favorited, 'solid' => '#0055fe33'])
        @icon('circle', ['name' => 'unsaved', 'mr' => 0, 'if' => ! $is_favorited])
    </span>
</button>
