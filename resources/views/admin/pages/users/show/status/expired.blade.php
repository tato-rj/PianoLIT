<div class="col-12">
	<div class="alert alert-yellow" role="alert">@icon('triangle-alert', ['mr' => 2]){{$user->first_name}} signed up on <strong>{{$user->created_at->toFormattedDateString()}}</strong> but has not subscribed for a membership plan yet.</div>
</div>