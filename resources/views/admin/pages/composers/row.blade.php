<tr>
  <td class="dataTables_main_column">
    <img src="{{storage($item->cover_path)}}" class="d-inline rounded-circle mr-1" style="width: 18px; vertical-align: sub">{{$item->name}} ({{$item->alive_on}})
  </td>

  <td>
    @toggle(['toggle' => $item->is_famous, 'route' => route('admin.composers.toggle-famous', $item->id)])
  </td>
  
  <td>{{$item->pieces_count}} {{str_plural('piece', $item->pieces_count)}}</td>

  @include('components.datatable.actions', ['actions' => [
      'other' => [['route' => route('email-preview.birthday.web', ['composer_id' => $item->id]), 'title' => 'See a preview of the birthday email', 'icon' => 'birthday-cake']],
      'edit' => route('admin.composers.edit', $item->id),
      'delete' => route('admin.composers.destroy', $item->id)
  ]])
</tr>
