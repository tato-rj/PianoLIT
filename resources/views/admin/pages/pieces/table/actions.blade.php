<div class="d-flex flex-nowrap justify-content-end align-items-center gap-2">
    @include('admin.components.datatables.actions.edit', ['action' => route('admin.pieces.edit', $item->id)])
    @include('admin.pages.pieces.table.schedule-action')
    @include('admin.components.datatables.actions.delete', ['action' => route('admin.pieces.destroy', $item->id)])
</div>
