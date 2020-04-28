@extends('webapp.layouts.app')

@push('header')
<style type="text/css">
.btn-outline {border-width: 1.4px;}
.btn-default {padding: .6em 2.8em;}
</style>
@endpush

@section('content')
@include('webapp.layouts.header')

<section class="text-center mb-5">
	@include('webapp.components.piece.level')
	<h4 class="mt-2 mb-1">{{$piece->medium_name}}</h4>
	<p class="text-muted">{{$piece->composer->name}}</p>
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