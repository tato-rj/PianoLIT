@extends('layouts.app', [
	'title' => 'Scales Tutor | ' . config('app.name'),
	'shareable' => [
		'keywords' => 'scale,arpeggio,music theory,fingering',
		'title' => 'Scales Tutor',
		'description' => 'All you need to know about the main scales at any time',
		'thumbnail' => asset('images/misc/thumbnails/scales.jpg'),
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
	'version' => '1.1',
	'title' => 'Scales Tutor', 
	'subtitle' => 'Select below the scale you need and we\'ll show the notes and fingering for each hand'])

<div class="container mb-4" id="key-container">
	@include('tools.technique.components.input')
	@include('tools.technique.components.key')
	<div class="row">
		@include('tools.technique.components.submit', ['type' => 'scales'])
		<div class="col-12 mb-6">
			<p>This tool will help you learn the notes and fingering for every scale in any key or mode. You will also find information about the different types of minor keys and special modes.</p>
			<p>Just select the key and mode you need and we will show you all the resources you need!</p>
		</div>
	</div>
</div>

<div class="container mb-6">
	@include('components.sections.feedback')
	@include('components.sections.youtube')
</div>

@include('tools.chords.error')
@endsection

@push('scripts')
@include('components.addthis')
@include('tools.technique.js')
@endpush