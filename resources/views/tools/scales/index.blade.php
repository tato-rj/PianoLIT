@extends('layouts.app', [
	'title' => 'Scales & Arpeggios | ' . config('app.name'),
	'shareable' => [
		'keywords' => 'scale,arpeggio,music theory,fingering',
		'title' => 'Scales & Arpeggios',
		'description' => 'All you need to know about the main scales and arpeggios at any time',
		'thumbnail' => asset('images/misc/thumbnails/scales.jpg'),
		'created_at' => carbon('29-08-2019'),
		'updated_at' => carbon('29-08-2019')
	]])

@push('header')
<style type="text/css">
.fadeInUp {
	animation-duration: .2s;
}
</style>
@endpush

@section('content')

@include('components.title', [
	'version' => '1.0',
	'title' => 'Scales & Arpeggios', 
	'subtitle' => 'Select below the scale you need and we\'ll show the notes and fingering for each hand'])

<div class="container mb-4">
	<div class="row mt-5 mb-6">

	</div>
</div>

<div class="container mb-6">
	@include('components.sections.feedback')
	@include('components.sections.youtube')
</div>

@endsection

@push('scripts')
<script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-5c872ce214693180"></script>

<script type="text/javascript">

</script>
@endpush