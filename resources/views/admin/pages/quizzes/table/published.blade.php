<div>
  @toggle(['toggle' => $item->published_at, 'route' => route('admin.quizzes.update-status', ['quiz' => $item->slug]), 'autoToggle' => true])
</div>