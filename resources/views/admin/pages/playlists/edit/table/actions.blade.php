<div class="d-flex flex-nowrap justify-content-end align-items-center gap-2">
    @include('admin.components.play', ['audio' => storage($item->audio_path)])
    @include('admin.components.table-action', ['tableAction' => ['url' => $item->is_public_domain ? (string) storage($item->score_path) : (string) $item->score_url, 'label' => 'View score', 'icon' => $item->is_public_domain ? 'file-text' : 'globe', 'iconOnly' => true, 'attributes' => ['target' => '_blank', 'rel' => 'noopener noreferrer']]])
    @include('admin.components.table-action', ['tableAction' => ['url' => route('webapp.pieces.show', $item->id), 'label' => 'View piece', 'icon' => 'eye', 'iconOnly' => true, 'attributes' => ['target' => '_blank', 'rel' => 'noopener noreferrer']]])
    <div class="add-piece" data-id="{{ $item->id }}">
        @include('admin.components.table-action', ['tableAction' => ['label' => 'Add piece', 'icon' => 'circle-plus', 'iconOnly' => true]])
        <div style="display: none;">
            @include('admin.pages.playlists.edit.piece', ['piece' => $item])
        </div>
    </div>
</div>
