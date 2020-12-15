<div>
 @toggle(['toggle' => $item->published_at, 'route' => route('admin.posts.update-status', $item->slug), 'autoToggle' => true])
</div>