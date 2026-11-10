<div class="text-end">
  @component('admin.components.datatables.actions', ['actions' => [
      'edit' => route('admin.quizzes.edit', $item->slug),
      'delete' => route('admin.quizzes.destroy', $item->slug)
  ]])
    @include('admin.pages.quizzes.table.preview', ['previewQuiz' => $item])
  @endcomponent
</div>
