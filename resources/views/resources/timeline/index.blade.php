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
<style type="text/css">
.fadeInUp {
	animation-duration: .2s;
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
		<div class="col-lg-8 col-md-8 col-12 mx-auto">
		  <div class="accordion" id="timeline">
        @foreach($timeline as $decade => $events)
        <div class="card border-0 mb-2">
          <div class="alert-grey px-4 py-3 cursor-pointer border-pill">
            <h6 class="mb-0 d-flex d-apart" data-toggle="collapse" data-target="#timeline-{{$decade}}" aria-expanded="true" aria-controls="timeline">
              <div>The {{$decade}}s</div>
              <div><i class="fas fa-caret-down"></i></div>
            </h6>
          </div>
          @foreach($events as $event)
          <div id="timeline-{{$decade}}" class="collapse" data-parent="#timeline">
            <div class="card-body">
              <span class="rounded px-2 py-1 mr-2 alert-teal"><strong>{{$event['year']}}</strong></span>{{$event['event']}}
            </div>
          </div>
          @endforeach
        </div>
        @endforeach  
      </div>
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
@endpush