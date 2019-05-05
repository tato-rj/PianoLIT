<label class="switch cursor-pointer">
	<input class="status-toggle" type="checkbox" data-target="#status-{{$user->id}}" {{$user->super_user ? 'checked' : null}} data-url="{{route('admin.users.super-status', $user->id)}}">
	<span class="slider round"></span>
</label>