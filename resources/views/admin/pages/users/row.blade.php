<tr>
  <td style="width: 16px">
    <div class="custom-control custom-checkbox">
      <input type="checkbox" class="custom-control-input check-user" data-id="{{$item->id}}" id="check-user-{{$item->id}}">
      <label class="custom-control-label" for="check-user-{{$item->id}}"></label>
    </div>
  </td>

  @include('components.datatable.date', ['date' => $item->created_at])
  
  <td title="Subscribed at {{$item->created_at->format('g:i:s a')}}" class="dataTables_main_column">{{$item->full_name}}</td>
  
  <td class="text-truncate" title="{{ucfirst($item->origin)}}">
    <i class="fas text-muted fa-{{$item->origin_icon}}"></i><span class="position-absolute invisible">{{$item->origin_icon}}</span>
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
  <td class="{{! is_null($lastActive) && $lastActive->isAfter(now()->subHour()) ? 'text-success' : null}}">{{$lastActive ? $lastActive->diffForHumans() : 'Never'}}</td>

  <td>
    @toggle(['toggle' => $item->super_user, 'route' => route('admin.users.super-status', $item->id)])
  </td>

  @include('components.datatable.actions', ['actions' => [
      'other' => [
        ['route' => "mailto:$item->email", 'title' => "Send an email to $item->first_name", 'icon' => 'envelope'],
        ['route' => route('impersonate', $item), 'title' => "Impersonate user", 'icon' => 'user-secret'],
        ['route' => route('admin.users.show', $item), 'title' => "More details", 'icon' => 'eye']
      ],
      'delete' => route('admin.users.destroy', $item)
  ]])
</tr>