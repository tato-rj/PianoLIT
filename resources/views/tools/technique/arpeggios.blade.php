@extends('layouts.app', [
	'title' => 'Arpeggios Tutor | ' . config('app.name'),
	'shareable' => [
		'keywords' => 'scale,arpeggio,music theory,fingering',
		'title' => 'Arpeggios Tutor',
		'description' => 'All you need to know about the main arpeggios at any time',
		'thumbnail' => asset('images/misc/thumbnails/arpeggios.jpg'),
		'created_at' => carbon('29-08-2019'),
		'updated_at' => carbon('29-08-2019')
	]])

@push('header')
<style type="text/css">
.fadeInUp {
	animation-duration: .2s;
}

#pills-tab .nav-link {
	color: #b8c2cc;
}

#pills-tab .active {
	color: #343a40!important;
	font-weight: bold;
}
</style>
@endpush

@section('content')

@include('components.title', [
	'version' => '2.0',
	'title' => 'Arpeggios Tutor', 
	'subtitle' => 'Select below the key you need and we\'ll show the notes and fingering for each hand'])

<div class="container mb-4" id="key-container">
	@include('tools.technique.components.input')
	@include('tools.technique.components.key')
	<div class="row">
		@include('tools.technique.components.submit', ['type' => 'arpeggios'])
		<div class="col-12 mb-5">
			<p>This tool will help you learn the notes and fingering for every arpeggio in any key. You will also find information about the different inversions for each arpeggio.</p>
			<p>Just select the key and mode you need and we will show you all the resources you need!</p>
		</div>
	    <div class="col-12 text-center mb-4">
	      <h6>Need help with <strong>Scales</strong>? <a href="{{route('tools.scales.index')}}">Click here</a></h6>
	    </div>
	</div>
</div>

<div class="container mb-6">
	@include('components.sections.feedback')
	@include('components.sections.youtube')
</div>

@include('tools.chord-finder.error')
@include('components.overlays.subscribe.crashcourse')
@endsection

@push('scripts')
<script type="text/javascript">
$("#crashcourse-overlay").showAfter(3);
</script>
@include('components.addthis')
@include('tools.technique.js')
@endpush