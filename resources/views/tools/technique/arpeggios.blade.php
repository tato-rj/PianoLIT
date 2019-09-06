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
	'version' => '1.0',
	'title' => 'Arpeggios Tutor', 
	'subtitle' => 'Select below the key you need and we\'ll show the notes and fingering for each hand'])

<div class="container mb-4" id="key-container">
	@include('tools.technique.components.input')
	@include('tools.technique.components.key')
	<div class="row">
		@include('tools.technique.components.submit', ['type' => 'arpeggios'])
		<div class="col-12 mb-6">
			<p>This tool will not only show you the name of the possible chords based on the notes you put in, but also help you understand how we do this! If you are a beginner this process may seem a bit overwhelming at first but don't worry, it will soon be quite simple.</p>
			<p>Just put the notes you have in the inputs above and we'll show you all the chords we can make with those notes. On the next screen, as you select each chord you will see detailed description about how we figured out its name.</p>
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