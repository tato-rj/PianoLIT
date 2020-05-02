<div class="col-12">
	@if($user->membership()->exists())
		@if($user->membership->source->renews_at)

		<div class="alert alert-green" role="alert"><i class="fas fa-check-circle mr-2"></i>
			{{$user->first_name}}'s membership is active! 
			The next auto-renewal date is in {{$user->membership->source->renews_at->diffForHumans()}} on <strong>{{$user->membership->source->renews_at->toFormattedDateString()}}</strong>.
		</div>

		@elseif($user->membership->source->expires_at)

		<div class="alert alert-red" role="alert"><i class="fas fa-check-circle mr-2"></i>
			{{$user->first_name}}'s membership is active, but the auto renewal is off. 
			It is se to expire in {{$user->membership->source->expires_at->diffForHumans()}} on <strong>{{$user->membership->source->expires_at->toFormattedDateString()}}</strong>.
		</div>
			
		@endif
	@else
		<div class="alert alert-green" role="alert"><i class="fas fa-check-circle mr-2"></i>
			{{$user->first_name}} has been granted full access to the app, for life!
		</div>
	@endif
</div>
@if($user->membership()->exists())
@include('admin.pages.users.show.membership.info')
@endif