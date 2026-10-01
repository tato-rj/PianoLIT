<div class="text-end">
  @include('components.datatable.actions', ['actions' => [
      'other' => [
	['route' => route('crashcourses.show', $item->slug), 'title' => 'Preview this course', 'icon' => 'eye']
      ],
      'edit' => route('admin.crashcourses.edit', $item->slug),
      'delete' => route('admin.crashcourses.destroy', $item->slug)
  ]])
</div>
