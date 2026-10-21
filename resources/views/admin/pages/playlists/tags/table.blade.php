@component('admin.components.table', [
    'id' => 'playlist-tags-table', 'label' => 'Playlist tags', 'items' => $playlistTags,
    'columns' => ['Tag', 'Playlists', ['label' => 'Actions', 'hidden' => true]],
    'emptyTitle' => 'No tags yet', 'emptyDescription' => 'Create your first tag above.',
])
    @foreach($playlistTags as $tag)
    <tr data-tag-id="{{ $tag->id }}" data-tag-name="{{ $tag->name }}">
        <td>
            <span class="admin-table__primary" data-tag-label>{{ $tag->name }}</span>
            <form id="playlist-tag-edit-{{ $tag->id }}" method="POST" action="{{ route('admin.playlists.tags.update', $tag) }}" data-tag-edit hidden>
                @csrf
                @method('PATCH')
                @input(['bag' => 'playlistTag', 'id' => 'playlist-tag-name-'.$tag->id, 'name' => 'name', 'oldInputKey' => 'playlist_tag_name_'.$tag->id, 'label' => 'Tag name', 'value' => $tag->name, 'limit' => 255, 'grid' => 'mb-0', 'autocomplete' => 'off'])
            </form>
        </td>
        <td>{{ number_format($tag->playlists_count) }}</td>
        <td>
            <div data-tag-read-actions><div class="d-flex flex-nowrap justify-content-end gap-2">
                @include('admin.components.table-action', ['tableAction' => ['kind' => 'edit', 'attributes' => ['data-tag-start-edit' => true, 'aria-label' => 'Edit tag '.$tag->name]]])
                @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'attributes' => ['data-tag-delete' => route('admin.playlists.tags.destroy', $tag), 'aria-label' => 'Delete tag '.$tag->name]]])
            </div></div>
            <div data-tag-edit-actions hidden><div class="d-flex flex-nowrap justify-content-end gap-2">
                @include('admin.components.table-action', ['tableAction' => ['label' => 'Save', 'icon' => 'check', 'type' => 'submit', 'attributes' => ['form' => 'playlist-tag-edit-'.$tag->id, 'aria-label' => 'Save tag '.$tag->name]]])
                <button type="button" class="btn btn-secondary btn-sm" data-tag-cancel>Cancel</button>
            </div></div>
        </td>
    </tr>
    @endforeach
@endcomponent
