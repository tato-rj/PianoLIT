@extends('layouts.app', [
	'title' => 'The (interactive) Circle of Fifths | ' . config('app.name'),
	'popup' => 'gift',
	'shareable' => [
		'keywords' => 'circle of fifths,music theory,circle,fifths,music theory,chords',
		'title' => 'The Interactive Circle of Fifths',
		'description' => 'An interactive Circle of Fifths that will help you understand what it is and how to use it.',
		'thumbnail' => asset('images/misc/thumbnails/circle.jpg'),
		'created_at' => carbon('20-06-2019'),
		'updated_at' => carbon('20-06-2019')
	]])

@push('header')
<link href="https://fonts.googleapis.com/css?family=Roboto+Slab:700&display=swap" rel="stylesheet">
@endpush

@section('content')

@pagetitle([
	'version' => '1.1',
	'title' => 'The Circle of Fifths', 
	'subtitle' => 'An interactive and fun tool to explore music harmony in an innovative way. Enjoy!'])
	
<div class="container mb-4">	
	<div class="row mb-6">
		<div class="col-lg-5 col-md-6 col-12 px-4 mb-6">
			<div id="wheel-container" class="w-100 position-relative">
				@include('tools.circle-of-fifths.wheel')
			</div>
			<div id="wheel-controls" class="w-100 d-flex align-items-center px-5">
				<button direction="right" class="border-0 bg-transparent p-0 text-grey"><i class="fas fa-3x fa-arrow-circle-left"></i></button>
				<div class="flex-grow text-grey text-center mx-2"><div><small>Use the arrows to turn the wheel</small></div></div>
				<button direction="left" class="border-0 bg-transparent p-0 text-grey"><i class="fas fa-3x fa-arrow-circle-right"></i></button>
			</div>
		</div>
		<div class="col-lg-7 col-md-6 col-12" id="labels-container">
			<div id="mode-controls" class=" mb-2">
				<ul class="nav nav-tabs mode-tabs mb-3" id="mode-tabs" role="tablist">
					<li class="nav-item">
						<a class="nav-link active" data-name="major" id="major-tab" data-toggle="tab" href="#mode-major" role="tab" aria-controls="major" aria-selected="true">Major</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" data-name="minor" id="minor-tab" data-toggle="tab" href="#mode-minor" role="tab" aria-controls="minor" aria-selected="false">Minor</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" data-name="enharmonic" id="enharmonic-tab" data-toggle="tab" href="#mode-enharmonic" role="tab" aria-controls="minor" aria-selected="false">Enharmonic</a>
					</li>
				</ul>
				<div class="tab-content p-1 t-2" id="mode-panels" style="opacity: 0;">
					@include('tools.circle-of-fifths.labels.major')
					@include('tools.circle-of-fifths.labels.minor')
					@include('tools.circle-of-fifths.labels.enharmonic')
				</div>
			</div>
		</div>
		<div class="col-12 mt-2 text-center">
			<p class="text-grey m-0">The scale on a piano keyboard</p>
			<p class="text-grey">Tap/click to play the notes</p>
			@include('components.piano.keyboard', [
				'centered' => true,
				'octaves' => [
					3 => [
						[true, false],
						[true, false],
						[true, false],
						[true, false],
						[true, false],
						[true, false],
						[true, false]
					],
					4 => []
				]
			])
		</div>
	</div>
</div>

@include('tools.circle-of-fifths.info.key')
@include('tools.circle-of-fifths.info.signature')
@include('tools.circle-of-fifths.info.relative')
@include('tools.circle-of-fifths.info.neighbors')
@include('tools.circle-of-fifths.info.functional')

{{-- @popup(['view' => 'subscription']) --}}
@endsection

@push('scripts')
@addthis
<script type="text/javascript" src="{{mix('js/tone.js')}}"></script>
<script type="text/javascript" src="{{asset('js/components/circle-of-fifths.js')}}"></script>
<script type="text/javascript" src="{{asset('js/components/piano.js')}}"></script>
@endpush