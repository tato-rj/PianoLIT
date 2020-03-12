<div>
  @toggle(['toggle' => $item->published_at, 'route' => route('admin.crashcourses.update-status', ['quiz' => $item->slug, 'attribute' => 'published_at'])])
</div>