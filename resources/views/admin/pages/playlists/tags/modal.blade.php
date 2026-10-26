@component('components.dialogs.modal', ['id' => 'playlist-tags-modal', 'header' => 'Manage tags', 'options' => ['size' => 'lg']])
@slot('body')
    <div data-playlist-tags data-csrf="{{ csrf_token() }}">
        <p class="text-muted">Use tags to group playlists. Renaming updates associated playlists; deleting removes their tag.</p>
        <div class="alert alert-red" role="alert" data-tag-error hidden></div>
        <p class="small text-muted" role="status" aria-live="polite" data-tag-status></p>
        <form method="POST" action="{{ route('admin.playlists.tags.store') }}" data-tag-create class="mb-4">
            @csrf
            <div class="d-flex flex-wrap align-items-end gap-3">
                <div class="flex-grow-1">
                    @input(['bag' => 'playlistTag', 'id' => 'playlist-tag-name', 'name' => 'name', 'oldInputKey' => 'playlist_tag_name', 'label' => 'New tag name', 'placeholder' => 'Enter a tag name', 'limit' => 255, 'grid' => 'mb-0', 'autocomplete' => 'off'])
                </div>
                <button type="submit" class="btn btn-dark">@icon('plus')Create tag</button>
            </div>
        </form>
        <div data-tag-list>@include('admin.pages.playlists.tags.table')</div>
    </div>
@endslot
@slot('footer')
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Done</button>
@endslot
@endcomponent
