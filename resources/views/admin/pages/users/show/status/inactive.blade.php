<div class="col-12 p-3">

	<div class="p-3 alert alert-danger" role="alert">
		<div>
			<i class="fas fa-ban mr-2"></i>
			{{$user->first_name}}'s subscription expired on <strong>{{$user->subscription->renews_at->toFormattedDateString()}}</strong> and it was last validated on {{$user->subscription->validated_at->toDayDateTimeString()}}.
		</div>
	</div>	

</div>

@include('projects/pianolit/users/show/subscription/info')