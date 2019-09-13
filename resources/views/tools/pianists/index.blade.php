@extends('layouts.app', [
	'title' => 'Pianists | ' . config('app.name'),
	'shareable' => [
		'keywords' => 'recordings,famous pianists,albums,classical music',
		'title' => 'Pianists',
		'description' => 'Discover recordings from the most famous pianists of our time',
		'thumbnail' => asset('images/misc/thumbnails/staff.jpg'),
		'created_at' => carbon('15-09-2019'),
		'updated_at' => carbon('15-09-2019')
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
	'title' => 'Pianists', 
	'subtitle' => 'Discover recordings from the most famous pianists of our time'])

<div class="container mb-4">
	<div class="row">
		@foreach($pianists as $pianist)
		@include('tools.pianists.card')
		@endforeach
	</div>
</div>

<div class="container mb-6">
	@include('components.sections.feedback')
	@include('components.sections.youtube')
</div>

@endsection

@push('scripts')
@include('components.addthis')

@endpush