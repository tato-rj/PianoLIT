@extends('webapp.layouts.app', ['title' => 'Discover'])

@section('content')
@component('webapp.layouts.header', ['title' => 'Discover', 'subtitle' => 'Take a quick tour to find the perfect piece for you'])
    @include('webapp.tour.button')
@endcomponent

<section id="discover-rows">
	@foreach($rows as $row)
		@include('webapp.discover.rows.' . $row['row'], compact('hasFullAccess'))
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