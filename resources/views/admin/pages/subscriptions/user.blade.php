@if($user)
<span><a href="{{route('admin.users.show', $user)}}" target="_blank" class="ml-2">Show user</a></span>
@else
<span class="ml-2 text-muted"><i>Not a user</i></span>
@endif