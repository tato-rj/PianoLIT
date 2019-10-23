<a href="{{route('admin.notifications.read', [
	'ids' => json_encode($notifications->pluck('id')),
	'url' => $notifications[0]->data['url']])}}" 
	class="list-group-item list-group-item-action border-0 rounded-0 cursor-pointer notification-item">
	<div class="clamp-2">
		@if(count($notifications) > 1)
		<span class="badge bg-blue text-white mr-1">{{count($notifications)}}</span>
		@endif
		{!! $notifications[0]->data['message'] !!}
	</div>
	<div><small>{{$notifications[0]->created_at->diffForHumans()}}</small></div>
</a>