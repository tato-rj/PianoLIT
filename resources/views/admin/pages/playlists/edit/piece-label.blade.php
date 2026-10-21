<div class="admin-playlist-piece">
    <a href="{{ route('webapp.pieces.show', $piece) }}" target="_blank" rel="noopener noreferrer" class="admin-table__primary clamp-2" title="{{ $piece->long_name }}">{{ $piece->long_name }}</a>
    <small>@include('components.composer-label', ['composer' => $piece->composer])</small>
</div>
