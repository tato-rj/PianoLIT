<div class="col-6">
	<table class="table table-striped table-borderless">
	  <tbody>
		@include('projects/pianolit/users/show/list-item', 
			['title' => 'Subscription ID', 'value' => $user->subscription->latest_receipt_info->original_transaction_id])
		@include('projects/pianolit/users/show/list-item', 
			['title' => 'Plan', 'value' => ucfirst($user->subscription->latest_receipt_info->product_id)])
		@include('projects/pianolit/users/show/list-item',
			['title' => 'Start date', 'value' => $user->subscription->created_at->toDayDateTimeString()])
		@include('projects/pianolit/users/show/list-item',
			['title' => 'Next due date', 'value' => $user->subscription->renews_at ? $user->subscription->renews_at->toDayDateTimeString() : '-'])
	  </tbody>
	</table>
</div>
<div class="col-6">
	@if($user->subscription->expired())
		<div class="mb-3">
			<form method="POST" action="{{route('piano-lit.users.subscription.verify')}}">
				{{csrf_field()}}
				<input type="hidden" name="user_id" value="{{$user->id}}">
				<button class="btn btn-sm btn-danger">Validate subscription</button>
			</form>
		</div>
	@endif
	<a href="" data-toggle="modal" data-target="#subscription-history" class="link-default">
		<div class="mb-2">Request receipts history</div>
	</a>

</div>