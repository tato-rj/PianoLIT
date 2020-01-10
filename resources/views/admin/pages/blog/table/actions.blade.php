<div class="text-right">
  @include('components.datatable.actions', ['actions' => [
      'other' => [['route' => route('posts.show', $item->slug), 'title' => 'Preview this post', 'icon' => 'eye']],
      'edit' => route('admin.posts.edit', $item->slug),
      'delete' => route('admin.posts.destroy', $item->slug)
  ]])
</div>
