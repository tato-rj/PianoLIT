@extends('webapp.layouts.app')

@push('header')
<style type="text/css">
#tour-modal button.selected {
	background-color: #f0f0f0 !important;
}
</style>
@endpush

@section('content')
@component('webapp.layouts.header', ['title' => 'Discover', 'subtitle' => 'Take a quick tour to find the perfect piece for you'])
	@button(['label' => 'FIND YOUR MATCH', 'wide' => true, 'theme' => 'outline-secondary', 'classes' => 'rounded-pill', 'modal' => 'tour-modal'])
@endcomponent

<section id="discover-rows" data-search-url="{{route('webapp.search')}}">
@foreach($rows as $row)
	@include('webapp.discover.rows.' . $row['row'])
@endforeach
</section>

@include('webapp.discover.tour.modal')
@endsection

@push('scripts')
<script type="text/javascript">
function resetTour() {
	$('#tour-modal #questions-count').text($('#tour-modal #questions h5').length);
	$('#tour-modal #questions-iteration').text($('#tour-modal #questions h5').index() + 1);
	$('#tour-modal #options button').removeClass('selected');

	$('#tour-modal #options .list-group').first().show();
	$('#tour-modal #options .list-group').not(':first').hide();

	$('#tour-modal #questions h5').first().show();
	$('#tour-modal #questions h5').not(':first').hide();

	$('#tour-modal #dots i').first().removeClass('opacity-4');
	$('#tour-modal #dots i').not(':first').addClass('opacity-4');

	$('#tour-modal button#next').text('CHOOSE AN OPTION TO START').disable();
}

$('#tour-modal').on('show.bs.modal', function (e) {
	resetTour();
});

$('#tour-modal #options button').on('click', function() {
	$(this).addClass('selected').siblings().removeClass('selected');
	$('#tour-modal button#next').text('NEXT').enable();
});

$('#tour-modal button#next').on('click', function() {
	if ($('#tour-modal #questions h5:visible').is(':last-child')) {
		alert('DONE!');
	} else {
		$('#tour-modal #options .list-group:visible').hide().next().show();
		$('#tour-modal #questions h5:visible').hide().next().show();
		$('#tour-modal #dots i').not('.opacity-4').addClass('opacity-4').next().removeClass('opacity-4');
		$('#tour-modal button#next').disable();
	}
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