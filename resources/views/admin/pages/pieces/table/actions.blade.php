<div class="d-flex flex-nowrap justify-content-end align-items-center gap-2">
    @include('components.datatable.actions.edit', ['action' => route('admin.pieces.edit', $item->id)])
    @include('components.datatable.actions.delete', ['action' => route('admin.pieces.destroy', $item->id)])
</div>
