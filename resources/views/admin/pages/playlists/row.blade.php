<tr>
  @include('components.datatable.date', ['date' => $item->created_at])

  <td class="dataTables_main_column">{{$item->name}}</td>

  <td>{{slug_str($item->group)}}</td>

  <td>{{$item->pieces_count}} pieces</td>
  
  @include('components.datatable.actions', ['actions' => [
      'edit' => route('admin.playlists.edit', $item->id),
      'delete' => route('admin.playlists.destroy', $item->id)
  ]])
</tr>