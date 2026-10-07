<article class="my-pieces-folder" data-folder-search="{{ $folder->name }} {{ $folderSearch->get($folder->id, '') }}">
    <div class="my-pieces-folder__heading">
        <span class="my-pieces-folder__icon" aria-hidden="true">@icon('folder-open', ['mr' => 0])</span>
        <div class="my-pieces-folder__identity">
            <h3><a href="{{ route('webapp.users.favorites.folders.show', $folder) }}">{{ $folder->name }}</a></h3>
            <span>{{ $folder->favorites_count }} {{ str_plural('piece', $folder->favorites_count) }}</span>
        </div>
        <div class="my-pieces-folder__menu dropdown">
            <button type="button" class="my-pieces-folder__menu-button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="Options for {{ $folder->name }}">
                @icon('ellipsis-vertical', ['mr' => 0])
            </button>
            <div class="dropdown-menu dropdown-menu-end">
                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#edit-folder-{{ $folder->id }}">Edit folder</button>
                <button type="button" class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#delete-folder-{{ $folder->id }}">Delete folder</button>
            </div>
        </div>
    </div>

    <div class="my-pieces-folder__preview">
        <span class="my-pieces-folder__label">In this folder</span>
        @forelse($previews as $preview)
            <div class="my-pieces-folder__piece">
                <span class="my-pieces-folder__piece-icon" aria-hidden="true">@icon('music', ['mr' => 0])</span>
                <div>
                    <strong>{{ $preview->short_name }}</strong>
                    <span>{{ $preview->composer->short_name }}</span>
                </div>
            </div>
        @empty
            <p class="my-pieces-folder__empty">No pieces saved yet</p>
        @endforelse
    </div>

    <a class="my-pieces-folder__open btn-primary" href="{{ route('webapp.users.favorites.folders.show', $folder) }}">
        <span>Open folder</span>
        @icon('arrow-right', ['mr' => 0])
    </a>
</article>
