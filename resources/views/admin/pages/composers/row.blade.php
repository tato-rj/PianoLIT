<tr>
  <td><img src="{{storage($composer->cover_path)}}" class="d-inline rounded-circle mr-1" style="width: 18px; vertical-align: sub"> {{$composer->name}} ({{$composer->alive_on}})</td>
  <td>@include('admin.components.toggle.composer')</td>
  <td>{{$composer->pieces_count}} {{str_plural('piece', $composer->pieces_count)}}</td>
  <td class="text-right" style="white-space: nowrap;">
      <a href="{{route('email-preview.birthday.web', ['composer_id' => $composer->id])}}" target="_blank" title="See a preview of the birthday email" class="text-muted mr-2"><i class="fas fa-birthday-cake"></i></a>
      @can('update', $composer)
      <a href="{{route('admin.composers.edit', $composer->id)}}" class="text-muted mr-2"><i class="far fa-edit align-middle"></i></a>
      <a href="" data-name="{{$composer->name}}" data-url="{{route('admin.composers.destroy', $composer->id)}}" data-toggle="modal" data-target="#delete-modal" class="delete text-muted"><i class="far fa-trash-alt align-middle"></i></a>
      @else
      <a href="{{route('admin.composers.edit', $composer->id)}}" target="_blank" class="text-muted mr-2"><i class="far fa-eye align-middle"></i></a>
      @endcan
  </td>

</tr>
