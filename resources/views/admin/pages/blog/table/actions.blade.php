<div class="text-end">
  @include('admin.components.datatables.actions', ['actions' => [
      'other' => [['route' => route('posts.show', $item->slug), 'title' => 'Preview this post', 'icon' => 'eye']],
      'edit' => route('admin.posts.edit', $item->slug),
      'delete' => route('admin.posts.destroy', $item->slug)
  ]])
</div>
