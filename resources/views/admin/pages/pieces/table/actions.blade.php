<div class="d-flex flex-nowrap justify-content-end align-items-center gap-2">
    @include('components.datatable.actions.edit', ['action' => route('admin.pieces.edit', $item->id)])
    @include('components.datatable.actions.delete', ['action' => route('admin.pieces.destroy', $item->id)])
    @if($item->is_free)
        @include('admin.components.table-action', ['tableAction' => ['label' => 'This piece is the current Freepick!', 'icon' => 'award', 'iconOnly' => true, 'attributes' => ['disabled' => true]]])
    @elseif($item->hasImage())
    <form method="POST" action="{{ route('admin.pieces.highlight', $item->id) }}" class="m-0">
        @csrf
        @method('PATCH')
        @include('admin.components.table-action', ['tableAction' => ['type' => 'submit', 'label' => 'Highlight this piece', 'icon' => 'award', 'iconOnly' => true]])
    </form>
    @endif
    <form method="POST" action="{{ route('admin.pieces.hijack', $item->id) }}" class="m-0">
        @csrf
        @method('PATCH')
        @include('admin.components.table-action', ['tableAction' => ['type' => 'submit', 'label' => 'Change this piece creation date to today', 'icon' => 'calendar-days', 'iconOnly' => true]])
    </form>
</div>
