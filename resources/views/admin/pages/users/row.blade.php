<tr>
  @include('components.datatable.checkbox', ['type' => 'user'])

  @include('components.datatable.date', ['date' => $item->created_at])
  
  <td title="Subscribed at {{$item->created_at->format('g:i:s a')}}" class="dataTables_main_column">{{$item->full_name}}</td>
  
  <td class="text-truncate {{$item->email_confirmed ? 'text-success' : 'text-muted'}}" title="{{$item->email_confirmed ? 'Confirmed email' : 'Unconfirmed email'}}">
    <i class="{{$item->origin_icon}}" style="font-size: {{$item->origin == 'ios'? '130%' : null}}"></i>
    <small class="ml-1">{{$item->origin == 'ios'? 'iOS' : ucfirst($item->origin)}}</small>
  </td>
  
  <td class="text-truncate">
    @if($item->membership()->exists())
      @if($item->membership->expired())
      <span class="text-muted"><i><small>validated {{$item->membership->validated_at->diffForHumans()}}</small></i></span>
      @else
      <div><i class="fas fa-credit-card"></i></div>
      @endif
    @else
      Guest
    @endif
  </td>
  @php($lastActive = $item->lastActive())
  <td class="{{! is_null($lastActive) && $lastActive->isAfter(now()->subHours(12)) ? 'text-success' : null}}">
    <span class="position-absolute invisible">{{! is_null($lastActive) ? $lastActive->timestamp : 0}}</span>
    {{$lastActive ? $lastActive->diffForHumans() : null}}
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