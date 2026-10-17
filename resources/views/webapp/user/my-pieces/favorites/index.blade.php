@php($canCreateFolder = app(\App\Services\WebApp\FavoriteAccess::class)->canCreate(auth('web')->user(), $folders->count()))
@php($folderTarget = $canCreateFolder ? '#new-folder-modal' : app(\App\Services\WebApp\AccessPolicy::class)->promptTarget(auth('web')->user(), 'folders'))
@php($folderColors = \App\Support\FolderPalette::colorsFor($folders))
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
            <button type="button" data-access-action="folders" data-bs-toggle="modal" data-bs-target="{{ $folderTarget }}" class="btn btn-secondary">
                @icon('plus')New folder
            </button>
        </div>
    </div>

    @component('components.modal', ['id' => 'new-folder-modal', 'header' => 'New folder'])
        @slot('body')
            <form method="POST" action="{{ route('webapp.users.favorites.folders.store') }}">
                @csrf
                @input(['bag' => 'default', 'name' => 'name', 'placeholder' => 'Folder name'])
                @include('webapp.user.my-pieces.favorites.color', ['pickerId' => 'new-folder-color', 'selectedColor' => \App\Support\FolderPalette::suggest($folderColors)])
                @submit(['label' => 'Create folder', 'block' => true])
            </form>
        @endslot
    @endcomponent

    <p class="my-pieces-favorites__search-status" data-folder-status role="status" aria-live="polite"></p>
    <p class="my-pieces-favorites__no-results" data-folder-no-results hidden>No folders match your search. Try another folder, piece or composer.</p>
    @if(app(\App\Services\WebApp\AccessPolicy::class)->cap('folders', auth('web')->user()) === 0)
        <div class="text-center py-4"><p>Favorite folders are currently locked.</p>@include('webapp.components.access-action', ['accessFeature' => 'folders'])</div>
    @endif
    <div class="my-pieces-folders" id="folder-grid">
        @foreach($folders as $folder)
            @php($folderColor = $folderColors->get($folder->id))
            @include('webapp.user.my-pieces.favorites.folders.folder', ['previews' => $folderPreviews->get($folder->id, collect())])
            @include('webapp.user.my-pieces.favorites.delete')
            @include('webapp.user.my-pieces.favorites.edit')
        @endforeach
        <button type="button" class="my-pieces-folder my-pieces-folder--create" data-access-action="folders" data-bs-toggle="modal" data-bs-target="{{ $folderTarget }}">
            <span class="my-pieces-folder__create-icon" aria-hidden="true">@icon('plus', ['mr' => 0])</span>
            <strong>New folder</strong>
            <span>Create a folder to organize<br>your pieces</span>
        </button>
    </div>
</section>
