@extends('webapp.layouts.app')

@push('header')
@endpush

@section('content')
@include('webapp.layouts.header')

<section class="text-center mb-4">
	@include('webapp.components.piece.level')
	<h4 class="mt-2 mb-1">{{$piece->medium_name}}</h4>
	<p>{{$piece->composer->name}}</p>
</section>

<section id="tabs-container">
	@include('webapp.piece.nav')
	<div class="tab-content p-3">
		@include('webapp.piece.tabs.audio')
		@include('webapp.piece.tabs.video')
		@include('webapp.piece.tabs.score')
		@include('webapp.piece.tabs.timeline')
	</div>
</section>

@endsection

@push('scripts')
@endpush