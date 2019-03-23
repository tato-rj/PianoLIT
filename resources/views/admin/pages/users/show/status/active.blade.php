<div class="col-12 p-3">
	@if($user->membership->renews_at)

		<div class="p-3 alert alert-success" role="alert"><i class="fas fa-check-circle mr-2"></i>
			{{$user->first_name}}'s susbcribed is active! 
			The next auto-renewal date is in {{$user->membership->renews_at->diffForHumans()}} on <strong>{{$user->membership->renews_at->toFormattedDateString()}}</strong>.
		</div>

	@elseif($user->membership->expires_at)

		<div class="p-3 alert alert-danger" role="alert"><i class="fas fa-check-circle mr-2"></i>
			{{$user->first_name}}'s membership is active, but the auto renewal is off. 
			It is se to expire in {{$user->membership->expires_at->diffForHumans()}} on <strong>{{$user->membership->expires_at->toFormattedDateString()}}</strong>.
		</div>
		
	@endif
</div>

@include('admin.pages.users.show.membership.info')