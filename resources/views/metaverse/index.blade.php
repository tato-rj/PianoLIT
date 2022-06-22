@extends('layouts.app', [
	'title' => 'Metaverse Events | ' . config('app.name'),
	'shareable' => [
		'keywords' => 'classical music metaverse, metaverse music, piano metaverse, piano concert metaverse, music recital metaverse',
		'title' => 'Metaverse Events',
		'description' => 'Below is the list of upcoming events in the metaverse, we hope to see you there!',
		'thumbnail' => asset('images/misc/thumbnails/staff.jpg'),
		'created_at' => carbon('28-08-2019'),
		'updated_at' => carbon('28-08-2019')
	]])

@push('header')
@endpush

@section('content')

@pagetitle([
	'title' => 'Events in the Metaverse', 
	'subtitle' => 'Below is the list of upcoming events in the metaverse, we hope to see you there!'])

<div class="container mb-4">
	<div class="row mb-6">
		<div class="col-lg-8 col-md-8 col-12 mx-auto">
			@foreach($events as $event)
			@include('metaverse.event')
			@endforeach
		</div>
	</div>

	@pagination(['collection' => $events])
</div>

@endsection

@push('scripts')
@addthis
@endpush