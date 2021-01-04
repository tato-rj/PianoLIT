@extends('layouts.app', [
	'title' => 'Music Timeline | ' . config('app.name'),
	'popup' => ['view' => 'gift'],
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
.border-pill span {
  opacity: 0;
}

.border-pill:hover span {
  opacity: 1;
}
</style>
@endpush

@section('content')

@pagetitle([
	'version' => '1.0',
	'title' => 'Music Timeline', 
	'subtitle' => 'View the major music events in connection with world history'])

<div class="container mb-4">
	<div class="row mb-6">
		<div class="col-lg-8 col-md-8 col-12 mx-auto">
		@include('timeline.carousel')
		</div>
	</div>
</div>

{{-- @popup(['view' => 'subscription']) --}}
@endsection

@push('scripts')
@addthis
<script type="text/javascript" src="{{asset('js/views/timeline.js')}}"></script>
@endpush