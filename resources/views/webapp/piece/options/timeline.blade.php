@extends('webapp.layouts.app', ['title' => 'Timeline'])

@push('header')
<style type="text/css">
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

@include('webapp.piece.options.header')

<h5 class="mb-3 text-center">Timeline</h5>

<section id="timeline">

	@each('webapp.piece.components.event', $timeline, 'event')

</section>

@include('webapp.piece.components.panel')
@endsection

@push('scripts')
<script type="text/javascript">
</script>
@endpush