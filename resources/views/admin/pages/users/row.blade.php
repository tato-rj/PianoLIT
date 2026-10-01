<tr>
  @include('components.datatable.checkbox', ['type' => 'user'])

  @include('components.datatable.date', ['date' => $item->created_at])

  <td>{{$item->id}}</td>

  <td class="dataTables_main_column">{{$item->full_name}}{!! $item->countryFlag !!}</td>

  <td class="text-truncate {{$item->email_confirmed ? 'text-blue' : 'text-muted'}}" title="{{$item->email_confirmed ? 'Confirmed email on ' . $item->email_verified_at->toFormattedDateString() : 'Unconfirmed email'}}">
    @icon($item->origin_icon, ['mr' => 0, 'styles' => 'font-size: ' . ($item->origin == 'ios'? '130%' : null)])
    <small class="ms-1">{{$item->origin == 'ios'? 'iOS' : ucfirst($item->origin)}}</small>
  </td>

  <td class="text-truncate">
    @include('admin.components.users.status.sm', ['elements' => $item->statusElements()])
  </td>

  <td>
    @toggle(['toggle' => $item->super_user, 'route' => route('admin.users.super-status', $item->id), 'autoToggle' => true])
  </td>

  <td>
    @include('components.datatable.actions', ['actions' => [
        'other' => [
          ['route' => "mailto:$item->email", 'title' => "Send an email to $item->first_name", 'icon' => 'mail'],
          ['route' => route('impersonate', $item), 'title' => "Impersonate user", 'icon' => 'contact-round'],
          ['route' => route('admin.users.show', $item), 'title' => "More details", 'icon' => 'eye', 'target' => null]
        ]
    ]])
  </td>
</tr>