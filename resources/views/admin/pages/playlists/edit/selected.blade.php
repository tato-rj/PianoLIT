@component('admin.components.table', [
    'id' => 'playlist-selected-table', 'label' => 'Playlist pieces', 'title' => 'Playlist pieces', 'items' => $selectedPieces,
    'description' => 'Drag a handle or use the arrows to arrange the pieces. Save changes to apply your selection.',
    'columns' => [['label' => 'Drag', 'hidden' => true], 'Position', 'Piece', 'Level', ['label' => 'Actions', 'hidden' => true]],
    'emptyTitle' => 'No pieces yet', 'emptyDescription' => 'Use Add pieces to start building this playlist.',
])
    @slot('toolbar')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <p class="small text-muted mb-0" data-playlist-count>{{ $selectedPieces->count() }} {{ $selectedPieces->count() === 1 ? 'piece' : 'pieces' }} in this playlist</p>
        <button type="button" class="btn btn-dark btn-sm" data-bs-toggle="modal" data-bs-target="#playlist-piece-finder">@icon('plus')Add pieces</button>
    </div>
    @endslot
    @foreach($selectedPieces as $piece)
        @include('admin.pages.playlists.edit.selected-row', ['position' => $loop->iteration, 'first' => $loop->first, 'last' => $loop->last])
    @endforeach
@endcomponent
