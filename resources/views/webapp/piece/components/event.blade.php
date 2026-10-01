<div class="timeline-event position-relative {{$event['highlight'] ? 'timeline-highlighted' : null}} py-3 pe-3 ps-4 ms-3 border-start">
	@if($event['year'])
	<h6 class="mb-1">{{$event['year']}}</h6>
	@endif
	<p class="text-muted mb-0">{{$event['event']}}</p>
</div>