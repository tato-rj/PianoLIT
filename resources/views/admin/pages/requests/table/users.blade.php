<div>
	{{$item->user->full_name}}
	@if($item->user->isOnTrial)
	@php($daysLeft = 7 - $item->user->membership->created_at->diffInDays(now()))
	<div class="badge badge-pill alert-{{$daysLeft <= 2 ? 'red' : 'yellow'}}">{{$daysLeft}} {{str_plural('day', $daysLeft)}} left</div>
	@endif
</div>