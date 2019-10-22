@extends('layouts.app', [
	'title' => 'Infographs | ' . config('app.name'),
	'shareable' => [
		'keywords' => 'infograph,learn music,music theory,music sheet,piano sheet,treble sheet,bass sheet',
		'title' => 'Infographs',
		'description' => 'Cool infographs about all music things related',
		'thumbnail' => null,
		'created_at' => carbon('28-08-2019'),
		'updated_at' => carbon('28-08-2019')
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
	'title' => 'Infographs', 
	'subtitle' => 'Cool infographs about all music things related'])

@if(app()->isLocal())
<div class="container mb-5">
	@include('components/animations/workers')
	<h3 class="text-grey text-center my-4">Coming up soon!</h3>
</div>
@else
<div class="my-6">
	@include('components/animations/workers')
	<h3 class="text-grey text-center my-4">Coming up soon!</h3>
</div>
@endif

<div class="container mb-6">
	{{-- @include('components.sections.feedback') --}}
	@include('components.sections.youtube')
</div>

@endsection

@push('scripts')
@include('components.addthis')

<script type="text/javascript">

</script>
@endpush