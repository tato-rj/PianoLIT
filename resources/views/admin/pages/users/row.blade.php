<tr>
  <td style="width: 16px">
    <div class="custom-control custom-checkbox">
      <input type="checkbox" class="custom-control-input check-user" data-id="{{$user->id}}" id="check-user-{{$user->id}}">
      <label class="custom-control-label" for="check-user-{{$user->id}}"></label>
    </div>
  </td>
  <td style="white-space: nowrap;">{{$user->created_at->toFormattedDateString()}}</td>
  <td title="Subscribed at {{$user->created_at->format('g:i:s a')}}">{{$user->full_name}}</td>
  <td class="text-truncate">{{$user->origin}}</td>
  <td class="text-truncate">
    @if($user->membership()->exists())
      @if($user->membership->expired())
      <span class="text-muted"><i><small>validated {{$user->membership->validated_at->diffForHumans()}}</small></i></span>
      @else
      <div><i class="fas fa-credit-card"></i></div>
      @endif
    @else
      Guest
    @endif
  </td>
  <td>@include('admin.components.toggle.super-user')</td>
  <td class="text-right" style="white-space: nowrap;">
    <a href="mailto:{{$user->email}}" title="Send an email to {{$user->first_name}}" target="_blank" class="text-muted mr-2"><i class="far fa-envelope align-middle"></i></a>
    <a href="{{route('admin.users.show', $user)}}" title="View details" class="text-muted mr-2"><i class="far fa-eye align-middle"></i></a>
    <a href="{{route('impersonate', $user)}}" title="Impersonate user" target="_blank" class="text-muted mr-2"><i class="fas fa-user-secret align-middle"></i></a>
    <a href="#" data-name="{{$user->full_name}}" title="Delete user" data-url="{{route('admin.users.destroy', $user)}}" data-toggle="modal" data-target="#delete-modal" class="delete text-danger"><i class="far fa-trash-alt align-middle"></i></a>
  </td>
</tr>