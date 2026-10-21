@component('admin.components.table', [
    'id' => 'playlist-finder-table', 'label' => 'Find pieces', 'items' => $pieces,
    'description' => number_format($pieces->total()).' matching pieces.',
    'action' => route('admin.playlists.pieces', $playlist), 'state' => $filters,
    'search' => ['label' => 'Find a piece', 'placeholder' => 'Piece, composer, catalogue, tag or ID'],
    'filters' => [
        ['name' => 'composer_id', 'label' => 'Composer', 'options' => ['' => 'All composers'] + $composers->all()],
        ['name' => 'level_id', 'label' => 'Level', 'options' => ['' => 'All levels'] + $levels->all()],
    ],
    'sorts' => \App\Services\Admin\PlaylistPieceFinder::SORTS,
    'showReset' => $filters['search'] !== '' || !empty($filters['composer_id']) || !empty($filters['level_id']) || $filters['sort'] !== 'updated_desc' || $filters['per_page'] !== \App\Support\AdminPagination::PER_PAGE,
    'resetUrl' => route('admin.playlists.pieces', $playlist),
    'columns' => [['label' => 'Piece', 'sort' => ['asc' => 'name_asc', 'desc' => 'name_desc']], ['label' => 'Level', 'sort' => ['asc' => 'level_asc', 'desc' => 'level_desc']], ['label' => 'Actions', 'hidden' => true]],
    'emptyTitle' => 'No matching pieces', 'emptyDescription' => 'Try another name, composer or level.',
])
    @foreach($pieces as $piece)
    <tr data-finder-piece="{{ $piece->id }}">
        <td>@include('admin.pages.playlists.edit.piece-label')</td>
        <td class="text-nowrap">@include('webapp.components.piece.level-indicator')</td>
        <td><div class="d-flex flex-nowrap justify-content-end gap-2">
            @include('admin.pages.playlists.edit.preview-actions')
            @include('admin.components.table-action', ['tableAction' => ['label' => 'Add', 'icon' => 'plus', 'attributes' => ['data-piece-add' => $piece->id, 'aria-label' => 'Add '.$piece->short_name.' to playlist']]])
        </div>
        <template data-piece-template>@include('admin.pages.playlists.edit.selected-row', ['position' => '', 'first' => false, 'last' => false])</template></td>
    </tr>
    @endforeach
@endcomponent
