<a href="{{route('admin.notifications.read', [
	'ids' => $notifications->pluck('id')->toArray(),
	'url' => $notifications[0]->data['url']])}}"
	class="notification-item admin-notification-item">
	<div class="admin-notification-meta">
		<span class="admin-notification-type">{{strtolower($notifications[0]->data['title'])}}</span>
		@if(count($notifications) > 1)
		<span class="admin-notification-count" aria-label="{{count($notifications)}} notifications">{{count($notifications)}}</span>
		@endif
	</div>
	<div class="admin-notification-message">
		{!! $notifications[0]->data['message'] !!}
	</div>
	<time class="admin-notification-time" datetime="{{$notifications[0]->created_at->toIso8601String()}}">{{$notifications[0]->created_at->diffForHumans()}}</time>
</a>
