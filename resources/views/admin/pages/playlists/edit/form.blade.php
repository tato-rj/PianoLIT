<div class="admin-editor-grid">
    @component('admin.components.editor.section', ['id' => 'playlist-details', 'title' => 'Playlist details', 'description' => 'Give this collection a clear name and a short introduction.'])
        @input(['bag' => 'default', 'id' => 'playlist-name', 'name' => 'name', 'label' => 'Playlist name', 'value' => $playlist->name, 'limit' => 255])
        @input(['bag' => 'default', 'id' => 'playlist-subtitle', 'name' => 'subtitle', 'label' => 'Subtitle', 'value' => $playlist->subtitle, 'limit' => 72])
        @textarea(['bag' => 'default', 'id' => 'playlist-description', 'name' => 'description', 'label' => 'Description', 'value' => $playlist->description, 'rows' => 4, 'limit' => 255])
        @select(['bag' => 'default', 'id' => 'playlist-edit-featured', 'name' => 'featured_tag_id', 'label' => 'Featured tag (optional)', 'choices' => ['' => 'No tag'] + $playlistTags->pluck('name', 'id')->all(), 'value' => $playlist->featured_tag_id, 'required' => false])
    @endcomponent
    <div class="admin-editor-stack">
        @component('admin.components.editor.section', ['id' => 'playlist-cover', 'title' => 'Cover image'])
            <div class="ratio ratio-16x9 mb-4"><img src="{{ $playlist->cover_path ? $playlist->cover_image : asset('images/misc/placeholder-image-wide.png') }}" alt="Playlist cover" class="rounded object-fit-cover" data-playlist-cover></div>
            @include('components.form.upload.file', ['bag' => 'default', 'id' => 'playlist-cover-file', 'name' => 'cover', 'label' => 'Choose a cover image', 'accept' => 'image/jpeg,image/png,image/gif,image/webp'])
            <p class="small text-muted mb-0">JPG, PNG, GIF or WebP. Up to 8 MB.</p>
        @endcomponent
        @component('admin.components.editor.section', ['id' => 'playlist-publication', 'title' => 'Publication'])
            @if(!$playlist->published_at)
                <span class="admin-table__badge admin-table__badge--muted">Draft</span>
                <p class="small text-muted mt-3 mb-0">This playlist has not been published. Publish it from the Playlists directory when it is ready.</p>
            @elseif($playlist->published_at->isFuture())
                <span class="admin-table__badge admin-table__badge--warning">Scheduled</span>
                <p class="small text-muted mt-3 mb-0">Publishes {{ $playlist->published_at->format('M j, Y') }}.</p>
            @else
                <span class="admin-table__badge admin-table__badge--green">Published</span>
                <p class="small text-muted mt-3 mb-0">Published {{ $playlist->published_at->toFormattedDateString() }}. Saved changes update this playlist.</p>
            @endif
        @endcomponent
    </div>
</div>
