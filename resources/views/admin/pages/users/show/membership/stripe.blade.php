<div>
	<table class="table table-striped table-borderless">
	  <tbody>
		@include('admin.pages.users.show.list-item', 
			['title' => 'Billing Source', 'value' => 'Stripe'])
		@include('admin.pages.users.show.list-item', 
			['title' => 'Membership ID', 'value' => $user->membership->source->stripe_id])
		@include('admin.pages.users.show.list-item', 
			['title' => 'Plan', 'value' => $user->membership->source->plan_name])
		@include('admin.pages.users.show.list-item',
			['title' => 'Start date', 'value' => $user->membership->source->created_at->toDayDateTimeString()])
		@include('admin.pages.users.show.list-item',
			['title' => 'Next due date', 'value' => $user->membership->source->renews_at ? $user->membership->source->renews_at->toDayDateTimeString() : 'No upcoming payments'])
	  </tbody>
	</table>
</div>

<div>
	<a href="https://dashboard.stripe.com/test/customers/{{$user->membership->source->stripe_id}}" class="link-default" target="_blank">@fa(['icon' => 'external-link-alt'])View user's profile on Stripe</a>
</div>
