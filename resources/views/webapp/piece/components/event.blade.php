<div class="timeline-event position-relative {{$event['highlight'] ? 'timeline-highlighted' : null}} py-3 pr-3 pl-4 ml-3 border-left">
	@if($event['year'])
	<h6 class="mb-1">{{$event['year']}}</h6>
	@endif
	<p class="text-muted mb-0">{{$event['event']}}</p>
</div>