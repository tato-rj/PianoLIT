<tr>
  @include('components.datatable.checkbox', ['type' => 'user'])

  @include('components.datatable.date', ['date' => $item->created_at])

  <td class="dataTables_main_column">{{$item->full_name}}</td>
    
  <td class="text-truncate {{$item->email_confirmed ? 'text-blue' : 'text-muted'}}" title="{{$item->email_confirmed ? 'Confirmed email on ' . $item->email_verified_at->toFormattedDateString() : 'Unconfirmed email'}}">
    <i class="{{$item->origin_icon}}" style="font-size: {{$item->origin == 'ios'? '130%' : null}}"></i>
    <small class="ml-1">{{$item->origin == 'ios'? 'iOS' : ucfirst($item->origin)}}</small>
  </td>

  <td class="text-truncate">
    @include('admin.components.users.status.sm', ['elements' => $item->statusElements()])
  </td>

  <td>
    @toggle(['toggle' => $item->super_user, 'route' => route('admin.users.super-status', $item->id)])
  </td>

  <td>
    @include('components.datatable.actions', ['actions' => [
        'other' => [
          ['route' => "mailto:$item->email", 'title' => "Send an email to $item->first_name", 'icon' => 'envelope'],
          ['route' => route('impersonate', $item), 'title' => "Impersonate user", 'icon' => 'user-secret'],
          ['route' => route('admin.users.show', $item), 'title' => "More details", 'icon' => 'eye', 'target' => null]
        ]
    ]])
  </td>
</tr>