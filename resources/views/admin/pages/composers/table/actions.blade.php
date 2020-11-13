<div class="text-right"> 
  @include('components.datatable.actions', ['actions' => [
      'edit' => route('admin.composers.edit', $item->id),
      'delete' => route('admin.composers.destroy', $item->id)
  ]])
</div>