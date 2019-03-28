<label class="switch cursor-pointer">
	<input class="status-toggle" type="checkbox" 
		data-target="#status-{{$subscription->id}}" {{$subscription->is_active ? 'checked' : null}} 
		data-url="{{route('admin.subscriptions.update-status', $subscription->email)}}">
	<span class="slider round"></span>
</label>