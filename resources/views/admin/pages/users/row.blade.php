<tr>
  @include('components.datatable.checkbox', ['type' => 'user'])

  @include('components.datatable.date', ['date' => $item->created_at])

  <td class="dataTables_main_column">{{$item->full_name}}</td>
    
  <td class="text-truncate" title="{{$item->membership()->exists() ? 'Membership validated ' . $item->membership->validated_at->diffForHumans() : 'Never subscribed with Apple'}}">
    {!! $item->membership_status == 'Member' ? '<div><i class="fas fa-credit-card"></i></div>' : $item->membership_status !!}
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