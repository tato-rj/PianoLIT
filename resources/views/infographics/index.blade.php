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

@pagetitle([
	'title' => 'Infographics', 
	'subtitle' => 'Cool infographics about all music things related'])

<div class="container mb-5" style="overflow-y: hidden">
	<div class="row mb-3">
		<div class="col-lg-6 col-md-7 col-10 mx-auto">
			<div class="search-bar position-relative">
				<i class="fas fa-search"></i>
				<input id="search-infograph" type="text" placeholder="Search here..." class="w-100 border-bottom">
			</div>
		</div>
	</div>

	<div class="d-flex flex-wrap flex-center mb-4" id="infographs-types">
		<a href="{{route('resources.infographs.index')}}" class="m-1 border-0 rounded-pill btn btn-teal">View all</a>
		@foreach($topics as $topic)
			<button data-topic="{{$topic->slug}}" class="infograph-type-btn m-1 btn border-0 rounded-pill btn-teal-outline">{{$topic->name}}</button>
		@endforeach
	</div>

	<div id="infographics-container" data-url-load="{{route('resources.infographs.load')}}" data-url-search="{{route('resources.infographs.search')}}" class="row no-gutters mb-4">
		@include('infographics.load')
	</div>

  	@pagination(['collection' => $infographs])
</div>

@endsection

@push('scripts')
@addthis
<script type="text/javascript" src="{{asset('js/components/infographics.js')}}"></script>
@endpush