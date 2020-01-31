@php($top_user = $item->isTopUser($logs_total_count, $item->logs_count))
<tr style="background-color: {{$top_user ? '#d7f3e33d' : null}};">
  @include('components.datatable.checkbox', ['type' => 'user'])

  @include('components.datatable.date', ['date' => $item->created_at])
  
  <td title="{{$top_user ? "$item->first_name is a our fan!" : null}}" class="dataTables_main_column">
    {{$item->full_name}}{!! $top_user ? '<i class="fas fa-trophy ml-2 text-success"></i>' : null !!}
  </td>

  <td class="{{$top_user ? 'font-weight-bold' : null}}">{{$item->logs_count}}</td>
  
  <td class="text-truncate {{$item->email_confirmed ? 'text-blue' : 'text-muted'}}" title="{{$item->email_confirmed ? 'Confirmed email' : 'Unconfirmed email'}}">
    <i class="{{$item->origin_icon}}" style="font-size: {{$item->origin == 'ios'? '130%' : null}}"></i>
    <small class="ml-1">{{$item->origin == 'ios'? 'iOS' : ucfirst($item->origin)}}</small>
  </td>
  
  <td class="text-truncate" title="{{$item->membership()->exists() ? 'Membership validated ' . $item->membership->validated_at->diffForHumans() : 'Never subscribed with Apple'}}">
    {!! $item->membership_status == 'Member' ? '<div><i class="fas fa-credit-card"></i></div>' : $item->membership_status !!}
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