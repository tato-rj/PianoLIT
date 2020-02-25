@if($user->trial_ends_at)
<div class="col-12 p-3">
	<div class="p-3 alert alert-light" role="alert"><i class="fas fa-exclamation-triangle mr-2"></i>{{$user->first_name}} has not signed up for a membership plan yet</div>
</div>
@endif