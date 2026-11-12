@php
    $searchTitle = 'Browse piano pieces';
    $searchDescription = 'Discover piano pieces by level, composer, and musical style. Explore new repertoire, curated collections, and the free weekly pick.';
    $canonical = route('webapp.discover');
    $searchEntity = ['@type' => 'WebPage', 'url' => $canonical, 'name' => $searchTitle, 'description' => $searchDescription];
@endphp
@extends('webapp.layouts.app', ['title' => $searchTitle])

@section('page-metadata')
    @include('components.seo.resource-head', ['siteName' => 'PianoLIT', 'searchImage' => asset('images/webapp/thumbnail.jpg')])
@endsection

@push('header')
<style>
.mb-4 {
    margin-bottom: 3rem !important;
}
</style>
@endpush

@section('content')
@component('webapp.layouts.header', ['title' => 'Discover', 'subtitle' => 'Browse piano pieces by level, composer, and musical style.', 'pageHeading' => true])
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

<nav class="d-flex flex-wrap justify-content-center gap-3 py-3" aria-label="More ways to browse piano repertoire">
    <a href="{{ route('webapp.pieces.index') }}">Browse all piano pieces</a>
    <a href="{{ route('webapp.search.results') }}">Search the repertoire</a>
    <a href="{{ route('webapp.playlists') }}">Piano collections</a>
</nav>

@include('webapp.tour.modal')
@include('webapp.discover.composers.modal')
@if($membershipConfirmation ?? null)
    @include('webapp.membership.confirmation')
@endif
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
