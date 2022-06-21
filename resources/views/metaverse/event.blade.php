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
				<p class="text-muted m-0">Venue: {{$event->location->venue}}</p>
				<p class="text-muted m-0">Program: {{$event->theme}}</p>
				<p class="text-muted m-0">Duration: {{$event->duration}}</p>
			</div>
			<div class="mb-4">
				<a href="{{$event->location->url}}" target="_blank" class="btn rounded-pill btn-outline-dark btn-wide">@fa(['icon' => 'fire'])Go to event</a>

			</div>
		</div>
	</div>
</div>