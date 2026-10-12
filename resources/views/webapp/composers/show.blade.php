@extends('webapp.layouts.app', ['title' => $composer->name])


@push('header')
<link rel="preload" href="{{ asset('css/vendor/flag-icon/flag-icon.min.css') }}" as="style">
<link href="{{ asset('css/vendor/flag-icon/flag-icon.min.css') }}" rel="stylesheet">
@endpush

@section('content')
@include('webapp.layouts.header')

<section id="composer-profile" aria-labelledby="composer-name">
	@include('webapp.composers.page-profile')

	@if($timeline->isNotEmpty())
	<section class="mt-5 pt-4 border-top" aria-labelledby="composer-timeline-title">
		<h2 id="composer-timeline-title" class="h5 mb-4">What was the world like</h2>
		@include('webapp.piece.components.timeline')
	</section>
	@endif
</section>
@endsection
