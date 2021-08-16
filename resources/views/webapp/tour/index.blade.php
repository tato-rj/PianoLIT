@extends('webapp.layouts.app', ['title' => 'Quick tour'])

@push('header')
<style type="text/css">
#tour-modal button.selected {
	background-color: #f0f0f0 !important;
}
</style>
@endpush

@section('content')
@include('webapp.layouts.header', ['title' => 'Find your match', 'subtitle' => 'Take a quick tour to find the perfect piece for you'])

@include('funnels.find-your-match.carousel')

<div class="py-5 text-center">
	<p class="lead mb-2">Help us get even better</p>
	<a href="mailto:{{config('app.emails.general')}}?subject=My feedback for the PianoLIT team" target="_blank" class="btn btn-wide rounded-pill btn-outline-secondary">
		@fa(['icon' => 'comment-dots'])GIVE YOUR FEEDBACK
	</a>
</div>
@endsection

@push('scripts')
@endpush