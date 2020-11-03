@extends('layouts.app')

@push('header')
@endpush

@section('content')

@include('home.sections._lead')
@include('home.sections.promo')
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
$(document).ready(function() {
    let $suggestions = $('#query-suggestions');
    
    setInterval(function() {
        $suggestions.find('li').last().detach().prependTo($suggestions).fadeIn('slow');
        $suggestions.find('li:visible').last().hide();
    }, 4000);
});
</script>

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