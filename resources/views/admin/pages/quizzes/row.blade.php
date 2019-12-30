<tr>
  @include('components.datatable.date', ['date' => $item->created_at])

  <td class="dataTables_main_column">{{$item->title}}</td>

  <td>{{count($item->questions)}} questions</td>  
  
  <td>
    @toggle(['toggle' => $item->published_at, 'route' => route('admin.quizzes.update-status', $item->slug)])
  </td>

  @include('components.datatable.actions', ['actions' => [
      'other' => [['route' => route('quizzes.show', $item->slug), 'title' => 'Preview this quiz', 'icon' => 'eye']],
      'edit' => route('admin.quizzes.edit', $item->slug),
      'delete' => route('admin.quizzes.destroy', $item->slug)
  ]])
</tr>