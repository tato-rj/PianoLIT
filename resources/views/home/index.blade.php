@extends('layouts.app')

@push('header')
@endpush

@section('content')

@include('home.sections._lead')
@env('production')
@include('home.sections.highlights')
@endenv
@env('local')
@include('home.sections.promo')
@else
@include('home.sections.bar')
@endenv
@include('home.sections.tags')
@include('home.sections.composition')
@include('home.sections.youtube')
@include('home.sections.testimonials')
	
@popup(['view' => 'subscription'])
@endsection

@push('scripts')
<script type="text/javascript" src="{{asset('js/components/testimonials.js')}}"></script>
<script type="text/javascript" src="{{asset('js/components/tagsearch.js')}}"></script>
<script type="text/javascript" src="{{asset('js/animations/phonescreens.js')}}"></script>

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