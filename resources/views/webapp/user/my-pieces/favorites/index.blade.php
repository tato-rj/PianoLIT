<section class="my-pieces-favorites" id="folders-list" aria-labelledby="favorites-heading">
    <div class="my-pieces-favorites__heading">
        <div>

            <p>{{ $folders->count() }} {{ str_plural('folder', $folders->count()) }} · {{ $folders->sum('favorites_count') }} {{ str_plural('piece', $folders->sum('favorites_count')) }}</p>
        </div>
        <div class="my-pieces-favorites__actions">
            <label class="my-pieces-favorites__search" hidden>
                <span class="visually-hidden">Search folders, pieces or composers</span>
                @icon('search', ['mr' => 0])
                <input type="search" id="folder-search" placeholder="Search folders or pieces…" aria-controls="folder-grid" autocomplete="off">
            </label>
            <button type="button" data-bs-toggle="modal" data-bs-target="#new-folder-modal" class="btn btn-secondary">
                @icon('plus') New folder
            </button>
        </div>
    </div>

    @component('components.modal', ['id' => 'new-folder-modal', 'header' => 'New folder'])
        @slot('body')
            <form method="POST" action="{{ route('webapp.users.favorites.folders.store') }}">
                @csrf
                @input(['bag' => 'default', 'name' => 'name', 'placeholder' => 'Folder name'])
                @submit(['label' => 'Create folder', 'block' => true])
            </form>
        @endslot
    @endcomponent

    <p class="my-pieces-favorites__search-status" data-folder-status role="status" aria-live="polite"></p>
    <p class="my-pieces-favorites__no-results" data-folder-no-results hidden>No folders match your search. Try another folder, piece or composer.</p>
    <div class="my-pieces-folders" id="folder-grid">
        @foreach($folders as $folder)
            @include('webapp.user.my-pieces.favorites.folders.folder', ['previews' => $folderPreviews->get($folder->id, collect())])
            @include('webapp.user.my-pieces.favorites.delete')
            @include('webapp.user.my-pieces.favorites.edit')
        @endforeach
        <button type="button" class="my-pieces-folder my-pieces-folder--create" data-bs-toggle="modal" data-bs-target="#new-folder-modal">
            <span class="my-pieces-folder__create-icon" aria-hidden="true">@icon('plus', ['mr' => 0])</span>
            <strong>New folder</strong>
            <span>Create a folder to organize<br>your pieces</span>
        </button>
    </div>
</section>
