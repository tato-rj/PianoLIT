@if($user->trial_ends_at)
<div class="text-muted" title="{{$user->first_name}}'s trial expired on {{$user->trial_ends_at->toFormattedDateString()}}">
	<i class="fas fa-stopwatch"></i>
</div>
@endif