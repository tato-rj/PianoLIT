<div class="text-end">
  @component('admin.components.datatables.actions', ['actions' => [
      'edit' => route('admin.posts.edit', $item->slug),
      'delete' => route('admin.posts.destroy', $item->slug)
  ]])
    @include('admin.pages.blog.table.preview', ['previewPost' => $item])
  @endcomponent
</div>
