@component('components.dialogs.modal', ['id' => 'add-modal', 'header' => 'Create a playlist', 'show' => $errors->getBag('playlistCreate')->any() ? true : null])
@slot('body')
    <p class="text-muted mb-4">Start with the details. Add pieces in the editor, then publish when the playlist is ready.</p>
    <form method="POST" action="{{ route('admin.playlists.store') }}" enctype="multipart/form-data">
        @csrf
        @if($errors->getBag('playlistCreate')->any())<div class="alert alert-red" role="alert">Please check the highlighted fields. Choose the cover file again if needed.</div>@endif
        @input(['bag' => 'playlistCreate', 'id' => 'playlist-create-name', 'name' => 'name', 'label' => 'Playlist name', 'limit' => 255])
        @input(['bag' => 'playlistCreate', 'id' => 'playlist-create-subtitle', 'name' => 'subtitle', 'label' => 'Subtitle', 'limit' => 72])
        @textarea(['bag' => 'playlistCreate', 'id' => 'playlist-create-description', 'name' => 'description', 'label' => 'Description', 'rows' => 3, 'limit' => 255])
        @select(['bag' => 'playlistCreate', 'id' => 'playlist-create-featured', 'name' => 'featured_tag_id', 'label' => 'Featured tag (optional)', 'classes' => 'js-playlist-tag-select', 'choices' => ['' => 'No tag'] + $playlistTags->pluck('name', 'id')->all(), 'required' => false])
        @include('components.forms.upload.file', ['bag' => 'playlistCreate', 'id' => 'playlist-create-cover', 'name' => 'cover', 'label' => 'Cover image (optional)', 'accept' => 'image/jpeg,image/png,image/gif,image/webp'])
        <p class="small text-muted">New playlists are saved as drafts.</p>
        <div class="d-flex justify-content-end flex-wrap gap-2 mt-4">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-dark">@icon('plus')Create playlist</button>
        </div>
    </form>
@endslot
@endcomponent
