@if($user->membership()->exists())
<div class="text-success" title="{{$user->first_name}}'s membership is active and set to renew on {{$user->membership->source->renews_at->toFormattedDateString()}}">
	<i class="fas fa-check-circle"></i>
</div>
@else
<div class="text-success" title="{{$user->first_name}} is a super user">
	<i class="fas fa-medal"></i>
</div>
@endif