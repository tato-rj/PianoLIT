<div class="col-12 p-3">
	@if($user->subscription->renews_at)

		<div class="p-3 alert alert-success" role="alert"><i class="fas fa-check-circle mr-2"></i>
			{{$user->first_name}}'s susbcribed is active! 
			The next auto-renewal date is in {{$user->subscription->renews_at->diffForHumans()}} on <strong>{{$user->subscription->renews_at->toFormattedDateString()}}</strong>.
		</div>

	@elseif($user->subscription->expires_at)

		<div class="p-3 alert alert-danger" role="alert"><i class="fas fa-check-circle mr-2"></i>
			{{$user->first_name}}'s subscription is active, but the auto renewal is off. 
			It is se to expire in {{$user->subscription->expires_at->diffForHumans()}} on <strong>{{$user->subscription->expires_at->toFormattedDateString()}}</strong>.
		</div>
		
	@endif
</div>

@include('projects/pianolit/users/show/subscription/info')