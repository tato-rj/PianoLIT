
<div class="mb-4">

	@if($user->hasMembershipWith('App\Billing\Sources\Apple'))
	@include('admin.pages.users.show.membership.apple')
	@elseif($user->hasMembershipWith('App\Billing\Sources\Stripe'))
	@include('admin.pages.users.show.membership.stripe')
	@else
	<h6 class="text-muted mb-5"><i>{{$user->first_name}} signed up {{$user->created_at->diffForHumans()}} and did not subscribe yet.</i></h6>
	@endif

</div>