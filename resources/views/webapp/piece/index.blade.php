@extends('webapp.layouts.app')

@push('header')
<style type="text/css">
.btn-outline {border-width: 1.4px;}
.btn-default {padding: .6em 2.8em;}
</style>
@endpush

@section('content')
@include('webapp.layouts.header')

@include('webapp.piece.header')

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
<script type="text/javascript">
</script>
@endpush