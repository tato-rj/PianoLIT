<div class="col-12 p-3">
	<div class="p-3 alert alert-warning" role="alert"><i class="fas fa-exclamation-triangle mr-2"></i>{{$user->first_name}} signed up on <strong>{{$user->created_at->toFormattedDateString()}}</strong> but hasn't subscribed yet.</div>
</div>