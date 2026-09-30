@php($is_favorited = $folder->piece_favorites_count > 0)
<button type="button" class="save-to-folder{{ $is_favorited ? ' is-saved' : '' }}"
    data-submit="favorite" data-target="#flag-{{ $piece->id }}" data-url="{{ route('webapp.users.favorites.update', ['piece' => $piece, 'folder_id' => $folder->id]) }}"
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
