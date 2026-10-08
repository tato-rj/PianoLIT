@extends('webapp.layouts.app', ['title' => 'Discover'])

@push('header')
<style>
.mb-4 {
    margin-bottom: 3rem !important;
}
</style>
@endpush

@section('content')
@component('webapp.layouts.header', ['title' => 'Discover', 'subtitle' => 'Take a quick tour to find the perfect piece for you'])
    @include('webapp.tour.button')
@endcomponent

<section id="discover-rows">
	@foreach($rows as $row)
		@if(in_array($row['title'], ['Equivalent to the Suzuki series', 'Equivalent to the RCM levels', 'Equivalent to the ABRSM levels']))
			@once
				@include('webapp.discover.rows.levels')
			@endonce
		@else
			@include('webapp.discover.rows.' . $row['row'], compact('hasFullAccess'))
		@endif
	@endforeach
</section>

<div class="py-5 text-center">
	<p class="lead mb-2">Help us get even better</p>
	<a href="mailto:{{config('app.emails.general')}}?subject=My feedback for the PianoLIT team" target="_blank" class="btn btn-secondary">
		@icon('message-circle-more')GIVE YOUR FEEDBACK
	</a>
</div>

@include('webapp.tour.modal')
@include('webapp.discover.composers.modal')
@endsection

@push('scripts')
{{-- TRIGGER LINK ON CLICK, NOT WHILE DRAGGING --}}
<script type="text/javascript">
 $(function() {
    var isDragging = false;
    $('.search-card, .piece-card')
    .mousedown(function() {
        $(window).mousemove(function() {
            isDragging = true;
            $(window).unbind("mousemove");
        });
    })
    .mouseup(function() {
        var wasDragging = isDragging;
        isDragging = false;
        $(window).unbind("mousemove");
        if (!wasDragging) {
            search($(this));
        }
    });
  });

function search(element) {
	goTo(element.attr('data-url'));
}
</script>
@endpush
