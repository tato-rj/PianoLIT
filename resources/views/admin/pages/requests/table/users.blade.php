<div>
	{{$item->user->full_name}}
	@if($item->user->isOnTrial)
	@php($daysLeft = 7 - $membership->created_at->diffInDays(now()))
	<div class="badge badge-pill alert-yellow">{{$daysLeft}} {{str_plural('day', $daysLeft)}} left</div>
	@endif
</div>