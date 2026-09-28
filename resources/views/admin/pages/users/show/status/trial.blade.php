<div class="col-12 p-3">
	<div class="p-3 alert alert-green" role="alert">
		@icon('triangle-alert', ['mr' => 2]){{$user->first_name}} is on trial! The plan will start in {{$user->membership->source->renews_at->diffForHumans()}} on <strong>{{$user->membership->source->renews_at->toFormattedDateString()}}</strong>
	</div>
</div>

@if($user->hasMembershipWith('App\Billing\Sources\Apple'))
@include('admin.pages.users.show.membership.apple')
@endif

@if($user->hasMembershipWith('App\Billing\Sources\Stripe'))
@include('admin.pages.users.show.membership.stripe')
@endif