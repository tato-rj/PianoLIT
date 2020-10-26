@extends('layouts.app', [
	'title' => 'Infographics | ' . config('app.name'),
	'noclicks' => true,
	'shareable' => [
		'keywords' => 'infographic,infograph,learn music,music theory,music sheet,piano sheet,treble sheet,bass sheet',
		'title' => 'Infographics',
		'description' => 'Cool infographics about all music things related',
		'thumbnail' => asset('images/misc/thumbnails/infographs.jpg'),
		'created_at' => carbon('28-08-2019'),
		'updated_at' => carbon('28-08-2019')
	]])

@section('content')

<section class="container mb-5"> 
	@pagetitle([
		'title' => 'Infographics', 
		'subtitle' => 'Cool infographics about all music things related'])
		
	@component('components.display.layout', [
		'links' => $infographs->links(),
		'topics' => $topics])

		@slot('items')
			@foreach($infographs as $item)
			@include('infographics.card')
			@endforeach
		@endslot
	
	@endcomponent

</section>

@endsection

@push('scripts')
@addthis
<script type="text/javascript" src="{{asset('js/components/infographics.js')}}"></script>
@endpush