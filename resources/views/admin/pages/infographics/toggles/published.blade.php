<div>
  @toggle(['toggle' => $item->published_at, 'route' => route('admin.infographs.update-status', ['infograph' => $item->slug, 'attribute' => 'published_at']), 'autoToggle' => true])
</div>