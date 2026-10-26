<div class="text-end">
  @include('admin.components.datatables.actions', ['actions' => [
      'other' => [['route' => route('escores.show', $item->slug), 'title' => 'Preview this eBook', 'icon' => 'eye']],
      'edit' => route('admin.escores.edit', $item->slug),
      'delete' => route('admin.escores.destroy', $item->slug)
  ]])
</div>
