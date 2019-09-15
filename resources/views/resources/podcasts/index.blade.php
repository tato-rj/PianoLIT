@extends('layouts.app', [
	'title' => 'Top Podcasts | ' . config('app.name'),
	'shareable' => [
		'keywords' => 'podcasts,learn music,music theory,music history',
		'title' => 'Top Podcasts',
		'description' => 'Here is a list of our favorite podcasts',
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
	'title' => 'Top Podcasts', 
	'subtitle' => 'Here is a list of our favorite podcasts'])

@if(app()->isLocal())
<div class="my-6">
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
$('.staff').on('click', function() {
	$('.staff').next('.controls').fadeOut(function() {
		$(this).find('a').hide();
	});

	$(this).next('.controls').fadeIn('fast', function() {
		$(this).find('a').show();
	});
});
</script>
@endpush