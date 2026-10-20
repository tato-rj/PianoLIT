<div class="d-flex align-items-start gap-3">
    @if($playlist->cover_path)
        <img src="{{ $playlist->cover_image }}" width="72" height="54" class="rounded-sm object-fit-cover flex-shrink-0" alt="" loading="lazy">
    @endif
    <div class="flex-grow-1">
        <div class="d-flex flex-wrap align-items-center gap-2">
            @if($link ?? true)
                <a class="admin-table__primary" href="{{ route('admin.playlists.edit', $playlist) }}">{{ $playlist->name }}</a>
            @else
                <span class="admin-table__primary">{{ $playlist->name }}</span>
            @endif
            @if(($link ?? true) && $playlist->is_featured)<span class="admin-table__badge admin-table__badge--blue">Featured</span>@endif
        </div>
        <small class="clamp-2">{{ $playlist->subtitle }}</small>
        @if($playlist->featured)<small>{{ $playlist->featured }}</small>@endif
    </div>
</div>
