<div>
  @toggle(['toggle' => $item->published_at, 'route' => route('admin.reviews.update-status', ['review' => $item->id]), 'autoToggle' => true])
</div>