<div class="mb-3 pb-3 {{$loop->last ? '' : 'border-bottom'}}">
	<div class="lead py-4" style="font-size: 1.5rem;">{{strtoupper($event->formatted_date)}}</div>

	<div class="row">
		<div class="col-lg-8 col-12 d-flex">
			<div style="width: 20%; min-width: 108px">
				<label>TIME</label>
				<h5>{{strtoupper($event->formatted_time)}}</h5>
				<div class="text-muted">@fa(['icon' => 'stopwatch', 'mr' => 1]){{$event->duration}}</div>
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
				</div>
			</div>
		</div>
		<div class="col-lg-4 col-12">
			<div class="mb-4">
				<div class="text-center">
					@if($event->description)
					<a href="{{$event->location->url}}" data-toggle="modal" data-target="#event-{{$event->id}}-description" class="d-block mb-1 btn rounded-pill btn-primary btn-wide">More info</a>
					@endif
					@include('metaverse.go')
				</div>
			</div>
		</div>

	</div>
</div>

@component('components.modal', ['id' => 'event-'.$event->id.'-description', 'header' => $event->theme])
@slot('body')
<p class="mb-4" style="white-space: pre-wrap;">{{$event->description}}</p>
@include('metaverse.go')
@endslot
@endcomponent