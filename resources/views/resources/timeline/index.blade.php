@extends('layouts.app', [
	'title' => 'Music Timeline | ' . config('app.name'),
	'shareable' => [
		'keywords' => 'music history,music theory,timeline,composers,famous pieces,symphony',
		'title' => 'Music Timeline',
		'description' => 'View the major music events in connection with world history',
		'thumbnail' => asset('images/misc/thumbnails/staff.jpg'),
		'created_at' => carbon('28-08-2019'),
		'updated_at' => carbon('28-08-2019')
	]])

@push('header')
<link rel="stylesheet" type="text/css" href="{{asset('css/vendor/vis-timeline-graph2d.min.css')}}">
<style type="text/css">
.fadeInUp {
	animation-duration: .2s;
}
.vis-timeline {
	border: 0;
}

.vis-item {
    position: absolute;
    color: #1b7b72;
    background-color: #cdefeb;
    display: inline-block;
    z-index: 1;
    border: 0;
}

.is-dot, .vis-item.vis-line {
    color: #60b1a9;
}

.vis-item.vis-selected {
    background-color: #fff3cd;
    border-color: #fff3cd;
    z-index: 2;
    color: #856404;
}
</style>
@endpush

@section('content')
@include('components.title', [
	'version' => '1.0',
	'title' => 'Music Timeline', 
	'subtitle' => 'View the major music events in connection with world history'])

@if(app()->isLocal() || request()->has('dev'))
<div class="container mb-4">
	<div class="row mb-6">
		<div class="col-12">
		<div id="visualization" data-events="{{json_encode($events)}}"></div>
		</div>
	</div>
</div>
@else
<div class="my-6">
  @include('components/animations/workers')
  <h3 class="text-grey text-center my-4">Coming up soon!</h3>
</div>
@endif

<div class="container mb-6">
	@include('components.sections.feedback')
	@include('components.sections.youtube')
</div>

@endsection

@push('scripts')
@include('components.addthis')
<script type="text/javascript" src="{{asset('js/vendor/vis-timeline-graph2d.min.js')}}"></script>
<script type="text/javascript">
  // DOM element where the Timeline will be attached
  var container = document.getElementById('visualization');
  let events = JSON.parse(container.getAttribute('data-events'));
  let start = moment(events[0]['start']).subtract(50, 'years');
  let end = moment(events[events.length-1]['start']).add(50, 'years');
console.log(start);
  // Create a DataSet (allows two way data-binding)
  var items = new vis.DataSet(events);

  // Configuration for the Timeline
  var options = {
    width: 'auto',
  	zoomMax: 4.73e+11,
  	zoomMin: 1.577e+11,
  	min: start,
  	max: end
  };

  // Create a Timeline
  var timeline = new vis.Timeline(container, items, options);
</script>
@endpush