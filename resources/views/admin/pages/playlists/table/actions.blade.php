<div class="text-right"> 
  @include('components.datatable.actions', ['actions' => [
      'edit' => route('admin.playlists.edit', $item->id),
      'delete' => route('admin.playlists.destroy', $item->id)
  ]])
</div>