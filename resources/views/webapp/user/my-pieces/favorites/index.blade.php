<section class="my-pieces-favorites" id="folders-list" aria-labelledby="favorites-heading">
    <div class="my-pieces-favorites__heading">
        <div>
            <h2 id="favorites-heading">Your favorites</h2>
            <p>{{ $folders->count() }} {{ str_plural('folder', $folders->count()) }} · {{ $folders->sum('favorites_count') }} {{ str_plural('piece', $folders->sum('favorites_count')) }}</p>
        </div>
        <button type="button" data-bs-toggle="modal" data-bs-target="#new-folder-modal" class="btn btn-secondary">
            @icon('plus') New folder
        </button>
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

    @if($folders->isNotEmpty())
        <div class="my-pieces-folders">
            @foreach($folders as $folder)
                @include('webapp.user.my-pieces.favorites.folders.folder', ['previews' => $folderPreviews->get($folder->id, collect())])
                @include('webapp.user.my-pieces.favorites.delete')
                @include('webapp.user.my-pieces.favorites.edit')
            @endforeach
        </div>
    @else
        @include('webapp.components.empty', [
            'icon' => 'empty-favorites',
            'title' => 'No favorites yet',
            'subtitle' => 'Tap ' . \App\Support\Icon::render('heart', ['mr' => 0, 'filled' => true]) . ' to add a piece to your favorites'])
    @endif
</section>
