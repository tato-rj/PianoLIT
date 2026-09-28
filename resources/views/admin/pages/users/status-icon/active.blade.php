@if($user->membership()->exists())
<div class="text-success" title="{{$user->first_name}}'s membership is active and set to renew on {{$user->membership->source->renews_at->toFormattedDateString()}}">
	@icon('circle-check', ['mr' => 0])
</div>
@else
<div class="text-success" title="{{$user->first_name}} is a super user">
	@icon('medal', ['mr' => 0])
</div>
@endif