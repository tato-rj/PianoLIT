@extends('webapp.layouts.app')

@push('header')
<style type="text/css">
.btn-outline {border-width: 1.4px;}
.btn-default {padding: .6em 2.8em;}

.timeline-event:before {
	content: '';
	width: 12px;
	height: 12px;
	border-radius: 50%;
	background-color: #dee2e6;
	position: absolute;
	left: -7px;
	bottom: 50%;
	transform: translateY(50%);
}

.timeline-highlighted:before {
	background-color: #0055fe!important;
    -webkit-box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
}
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
		@include('webapp.piece.components.panel')
	</div>

</section>

@endsection

@push('scripts')
<script type="text/javascript">
$('a.toggle-favorite span').click(function() {
	$(this).siblings('button').click();
});
</script>
@endpush