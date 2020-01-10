<div class="text-right">
	<button class="btn btn-sm btn-outline-secondary m-0 mark-notification" style="display: {{$item->read_at ? 'inline-block' : 'none'}}" 
		data-url="{{route('admin.notifications.unread', ['ids' => [$item->id]])}}">Mark as unread</button>

	<button class="btn btn-sm btn-outline-success m-0 mark-notification" style="display: {{$item->read_at ? 'none' : 'inline-block'}}" 
		data-url="{{route('admin.notifications.read', ['ids' => [$item->id]])}}">Mark as read</button>
</div>