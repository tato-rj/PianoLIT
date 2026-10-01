@if($user)
<span><a href="{{route('admin.users.show', $user)}}" target="_blank" class="ms-2">Show user</a></span>
@else
<span class="ms-2 text-muted"><i>Not a user</i></span>
@endif