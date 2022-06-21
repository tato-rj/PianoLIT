<div class="mb-3 pb-3 {{$loop->last ? '' : 'border-bottom'}}">
	<div class="lead py-4" style="font-size: 1.5rem;">{{strtoupper($event->formatted_date)}}</div>

	<div class="d-flex">
		<div style="width: 20%; min-width: 108px">
			<label>TIME</label>
			<h5>{{strtoupper($event->formatted_time)}}</h5>
		</div>
		<div class="d-flex flex-wrap flex-grow">
			<div class="flex-grow mb-4">
				<label>WHERE</label>
				<div class="d-flex align-items-center mb-3">
					<img class="mr-2" style="width: 40px" src="{{$event->location->icon}}">
					<h5 class="m-0">{{$event->location->name}}</h5>
				</div>
				<p class="m-0">Venue: <span class="text-muted">{{$event->location->venue}}</span></p>
				<p class="m-0">Program: <span class="text-muted">{{$event->theme}}</span></p>
				<p class="m-0">Duration: <span class="text-muted">{{$event->duration}}</span></p>
			</div>
			<div class="mb-4">
				<div class="text-center">
					<a href="{{$event->location->url}}" target="_blank" class="d-block mb-1 btn rounded-pill btn-outline-dark btn-wide">@fa(['icon' => 'fire'])Go to event</a>
					<small class="text-muted">Capacity: {{$event->location->formatted_capacity}}</small>
				</div>
				
			</div>
		</div>
	</div>
</div>