<div class="mb-4">
	<h5 class="mb-3 text-center">The {{$century}} Century</h5>
	@foreach($decades as $decade => $events)
	@include('resources/timeline/decade')
	@endforeach
</div>