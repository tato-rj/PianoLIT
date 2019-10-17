<div style="margin-top: .75rem">
	<div><small>Also in this same year</small></div>
	<div>
		@foreach($timeline->get() as $event)
		<div><strong><small>{{$event->event}}</small></strong></div>
		@endforeach
	</div>
</div>