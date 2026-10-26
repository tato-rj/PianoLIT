<div class="text-end">
  @include('admin.components.datatables.actions', ['actions' => [
      'edit' => route('admin.composers.edit', $item->id),
      'delete' => route('admin.composers.destroy', $item->id)
  ]])
</div>