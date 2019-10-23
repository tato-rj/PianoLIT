<div data-target="{{$notification->data['url']}}" 
	data-url="{{route('admin.notifications.read', ['id' => $notification->id])}}" 
	class="list-group-item list-group-item-action border-0 rounded-0 cursor-pointer notification-item">
	<div class="clamp-2">
		@if($count > 1)
		<span class="badge bg-blue text-white mr-1">{{$count}}</span>
		@endif
		{!! $notification->data['message'] !!}
	</div>
	<div><small>{{$notification->created_at->diffForHumans()}}</small></div>
</div>