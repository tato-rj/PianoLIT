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
	@button([
		'label' => 'FIND YOUR MATCH', 
		'styles' => [
			'size' => 'wide', 
			'theme' => 'outline-secondary'
			], 
		'classes' => 'rounded-pill', 
		'data' => ['toggle' => 'modal', 'target' => '#tour-modal']])
@endcomponent

<section id="discover-rows">
	@foreach($rows as $row)
		@include('webapp.discover.rows.' . $row['row'], compact('hasFullAccess'))
	@endforeach
</section>

<div class="py-5 text-center">
	<p class="lead mb-2">Help us get even better</p>
	<a href="mailto:{{config('app.emails.general')}}?subject=My feedback for the PianoLIT team" target="_blank" class="btn btn-wide rounded-pill btn-outline-secondary">
		@fa(['icon' => 'comment-dots'])GIVE YOUR FEEDBACK
	</a>
</div>

@include('webapp.discover.tour.modal')
@include('webapp.discover.composers.modal')
@endsection

@push('scripts')
{{-- TOUR --}}
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
		let levelNames = ['elementary', 'beginner', 'intermediate', 'advanced'];
		let levelsArray = $('#tour-modal #options .list-group .selected').slice(0,2).attrToArray('data-tag');
		let tags = $('#tour-modal #options .list-group .selected').slice(2).attrToArray('data-tag');
		tags.push(levelNames[average(levelsArray)]);
		let url = window.urls.search + '?search=' + tags.join(' ');
		
		goTo(url);
	} else {
		$('#tour-modal #options .list-group:visible').hide().next().show();
		$('#tour-modal #questions h5:visible').hide().next().show();
		$('#tour-modal #dots i').not('.opacity-4').addClass('opacity-4').next().removeClass('opacity-4');
		$('#tour-modal button#next').disable();
	}
});

function average(array) {
  let total = 0;
  for(var i = 0; i < array.length; i++) {
      total += parseInt(array[i]);
  }
  return Math.floor(total / array.length);
}
</script>

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