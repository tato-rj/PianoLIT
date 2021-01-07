@extends('layouts.app', [
	'title' => 'Scales Tutor | ' . config('app.name'),
    'popup' => ['view' => 'ebook', 'always' => true, 'product' => \App\Shop\eBook::latest()->first()],
	'shareable' => [
		'keywords' => 'scale,arpeggio,music theory,fingering',
		'title' => 'Scales Tutor',
		'description' => 'All you need to know about the main scales at any time',
		'thumbnail' => asset('images/misc/thumbnails/scales.jpg'),
		'created_at' => carbon('29-08-2019'),
		'updated_at' => carbon('29-08-2019')
	]])

@section('content')

@pagetitle([
	'version' => '2.0',
	'title' => 'Scales Tutor', 
	'subtitle' => 'Select below the scale you need and we\'ll show the notes and fingering for each hand'])

<div class="container mb-5">
	@component('components.display.layout', [
		'ads' => ['ebook']
	])

	@slot('content')
	<div class="container mb-4" id="key-container">
		@include('tools.technique.components.input')
		@include('tools.technique.components.key')
		<div class="row">
			@include('tools.technique.components.submit', ['type' => 'scales'])
			<div class="col-12 mb-5">
				<p>This tool will help you learn the notes and fingering for every scale in any key or mode. You will also find information about the different types of minor keys and special modes.</p>
				<p>Just select the key and mode you need and we will show you all the resources you need!</p>
			</div>
	    <div class="col-12 text-center mb-4">
	      <h6>Need help with <strong>Arpeggios</strong>? <a href="{{route('tools.arpeggios.index')}}">Click here</a></h6>
	    </div>
		</div>
	</div>
	@endslot
	
	@endcomponent
</div>

@include('tools.chord-finder.error')
@endsection

@push('scripts')
@addthis
<script type="text/javascript" src="{{mix('js/tone.js')}}"></script>
<script type="text/javascript" src="{{asset('js/components/piano.js')}}"></script>
<script type="text/javascript" src="{{asset('js/components/play-keyboard.js')}}"></script>
@endpush