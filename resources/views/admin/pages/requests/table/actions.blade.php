<div class="d-flex flex-nowrap align-items-center gap-2">
    @include('admin.components.table-action', ['tableAction' => ['url' => route('webapp.pieces.show', $item->piece), 'label' => 'View', 'attributes' => ['target' => '_blank', 'rel' => 'noopener noreferrer']]])
    @include('admin.components.table-action', ['tableAction' => ['label' => 'Details', 'class' => 'view-request-types', 'attributes' => ['disabled' => !$item->types, 'data-types' => json_encode(unserialize($item->types))]]])
    @if($item->isPublished())
        <div class="text-success text-nowrap">@icon('circle-check', ['mr' => 1])Published</div>
    @else
        @include('admin.components.table-action', ['tableAction' => ['label' => 'Publish', 'icon' => 'hourglass', 'attributes' => ['data-url' => route('admin.tutorial-requests.publish', $item->id), 'data-bs-toggle' => 'modal', 'data-bs-target' => '#publish-tutorial']]])
        @include('admin.components.table-action', ['tableAction' => ['kind' => 'delete', 'class' => 'delete', 'attributes' => ['data-url' => route('admin.tutorial-requests.destroy', $item), 'data-bs-toggle' => 'modal', 'data-bs-target' => '#delete-modal']]])
    @endif
</div>
