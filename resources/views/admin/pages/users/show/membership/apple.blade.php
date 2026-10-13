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
	<a class="btn btn-sm btn-secondary mb-3" href="{{route('admin.analytics.memberships', ['provider' => 'apple', 'search' => $user->id])}}">View automatic membership checks</a>
	<button data-bs-toggle="modal" data-bs-target="#membership-history" class="btn btn-link">
		@icon('brand-apple')Request receipts history
	</button>
</div>
