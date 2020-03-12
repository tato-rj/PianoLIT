<div class="text-right">
  @include('components.datatable.actions', ['actions' => [
      'other' => [['route' => route('quizzes.show', $item->slug), 'title' => 'Preview this course', 'icon' => 'eye']],
      'edit' => route('admin.crashcourses.edit', $item->slug),
      'delete' => route('admin.crashcourses.destroy', $item->slug)
  ]])
</div>
