<div class="text-end">
  @include('admin.components.datatables.actions', ['actions' => [
      'other' => [['route' => route('ebooks.show', $item->slug), 'title' => 'Preview this eBook', 'icon' => 'eye']],
      'edit' => route('admin.ebooks.edit', $item->slug),
      'delete' => route('admin.ebooks.destroy', $item->slug)
  ]])
</div>
