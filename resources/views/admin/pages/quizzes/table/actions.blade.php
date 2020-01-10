<div class="text-right">
  @include('components.datatable.actions', ['actions' => [
      'other' => [['route' => route('quizzes.show', $item->slug), 'title' => 'Preview this quiz', 'icon' => 'eye']],
      'edit' => route('admin.quizzes.edit', $item->slug),
      'delete' => route('admin.quizzes.destroy', $item->slug)
  ]])
</div>
