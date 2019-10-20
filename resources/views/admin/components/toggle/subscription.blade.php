<label class="switch cursor-pointer">
	<input class="status-toggle" type="checkbox" 
		{{$subscription->getStatusFor($list, $boolean = true) ? 'checked' : null}} 
		data-url="{{route('subscriptions.toggle-status', ['subscription' => $subscription->email, 'list' => $list])}}">
	<span class="slider round"></span>
</label>