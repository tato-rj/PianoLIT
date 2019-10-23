<div data-target="{{$notification->data['url']}}" 
	data-url="{{route('admin.notifications.read', ['id' => $notification->id])}}" 
	class="list-group-item list-group-item-action border-0 rounded-0 cursor-pointer notification-item">
	<div class="clamp-2">{!! $notification->data['message'] !!}</div>
	<div><small>{{$notification->created_at->diffForHumans()}}</small></div>
</div>