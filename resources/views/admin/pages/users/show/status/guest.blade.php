<div class="col-12">
	<div class="alert alert-yellow" role="alert"><i class="fas fa-exclamation-triangle mr-2"></i>{{$user->first_name}} signed up on <strong>{{$user->created_at->toFormattedDateString()}}</strong> but has not subscribed for a membership plan yet.</div>
</div>