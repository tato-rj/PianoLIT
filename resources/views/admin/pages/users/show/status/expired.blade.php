<div class="col-12 p-3">
	<div class="p-3 alert alert-light" role="alert"><i class="fas fa-exclamation-triangle mr-2"></i>{{$user->first_name}}'s trial expired {{$user->trial_ends_at->diffForHumans()}} on <strong>{{$user->trial_ends_at->toFormattedDateString()}}</strong> and a subscription was never created.</div>
</div>
