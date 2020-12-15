<div>
	<table class="table table-striped table-borderless">
	  <tbody>
		@include('admin.pages.users.show.list-item', 
			['title' => 'Billing Source', 'value' => 'Apple In-App Purchases'])
		@include('admin.pages.users.show.list-item', 
			['title' => 'Membership ID', 'value' => $user->membership->source->latest_receipt_info ? $user->membership->source->latest_receipt_info->original_transaction_id : null])
		@include('admin.pages.users.show.list-item', 
			['title' => 'Plan', 'value' => $user->membership->source->latest_receipt_info ? ucfirst($user->membership->source->latest_receipt_info->product_id) : null])
		@include('admin.pages.users.show.list-item',
			['title' => 'Start date', 'value' => $user->membership->source->created_at->toDayDateTimeString()])
		@include('admin.pages.users.show.list-item',
			['title' => 'Next due date', 'value' => $user->membership->source->renews_at ? $user->membership->source->renews_at->toDayDateTimeString() : '-'])
	  </tbody>
	</table>
</div>

<div>
	@if($user->membership->source->isExpired() || ! $user->membership->source->renews_at)
		<div class="mb-3">
			<form method="POST" action="{{route('admin.memberships.validate.user', $user->id)}}">
				@csrf
				<input type="hidden" name="user_id" value="{{$user->id}}">
				<button class="btn btn-sm btn-danger">Validate membership</button>
			</form>
		</div>
	@endif
	<button data-toggle="modal" data-target="#membership-history" class="btn btn-link">
		@fa(['fa_type' => 'b', 'icon' => 'apple'])Request receipts history
	</button>
</div>