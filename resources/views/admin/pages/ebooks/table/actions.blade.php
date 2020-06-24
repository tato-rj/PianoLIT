<div class="text-right">
  @include('components.datatable.actions', ['actions' => [
      'other' => [['route' => route('ebooks.show', $item->slug), 'title' => 'Preview this eBook', 'icon' => 'eye']],
      'edit' => route('admin.ebooks.edit', $item->slug),
      'delete' => route('admin.ebooks.destroy', $item->slug)
  ]])
</div>
