<tr>
  @include('admin.components.datatables.date', ['date' => $item->created_at])

  <td>{{$item->id}}</td>

  <td class="dataTables_main_column">{{$item->full_name}}{!! $item->countryFlag !!}</td>

  <td class="text-truncate {{$item->email_confirmed ? 'text-blue' : 'text-muted'}}" title="{{$item->email_confirmed ? 'Confirmed email on ' . $item->email_verified_at->toFormattedDateString() : 'Unconfirmed email'}}">
    @icon($item->origin === 'webapp' ? 'pianolit' : $item->origin_icon, ['mr' => 0, 'styles' => 'font-size: ' . ($item->origin == 'ios'? '130%' : null)])
    <small class="ms-1">{{$item->origin == 'ios'? 'iOS' : ucfirst($item->origin)}}</small>
  </td>

  <td class="text-truncate">
    @include('admin.components.users.status.sm', ['elements' => $item->statusElements()])
  </td>

  <td>
    @include('admin.pages.users.table.actions')
  </td>
</tr>
