@extends('layouts.app', ['title' => 'Pianists | ' . config('app.name')])

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