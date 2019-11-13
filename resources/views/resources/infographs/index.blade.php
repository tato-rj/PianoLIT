@extends('layouts.app', [
	'title' => 'Infographs | ' . config('app.name'),
	'shareable' => [
		'keywords' => 'infograph,learn music,music theory,music sheet,piano sheet,treble sheet,bass sheet',
		'title' => 'Infographs',
		'description' => 'Cool infographs about all music things related',
		'thumbnail' => asset('images/misc/thumbnails/infographs.jpg'),
		'created_at' => carbon('28-08-2019'),
		'updated_at' => carbon('28-08-2019')
	]])

@push('header')
<style type="text/css">

</style>
@endpush

@section('content')
@include('components.title', [
	'title' => 'Infographs', 
	'subtitle' => 'Cool infographs about all music things related'])

<div class="container mb-5">
	<div class="row mb-3">
		<div class="col-lg-6 col-md-7 col-10 mx-auto">
			<div class="search-bar position-relative">
				<i class="fas fa-search"></i>
				<input id="search-infograph" type="text" placeholder="Search here..." class="w-100 border-bottom">
			</div>
		</div>
	</div>

	<div class="d-flex flex-wrap flex-center mb-4">
		<button data-target=".thumbnail" class="infograph-type-btn m-1 border-0 rounded-pill btn btn-teal">View all</button>
		@foreach($types as $type => $count)
		@if($count > 0)
		<button data-target=".thumbnail-{{$type}}" class="infograph-type-btn m-1 btn border-0 rounded-pill btn-teal-outline">{{ucfirst($type)}} ({{$count}})</button>
		@endif
		@endforeach
	</div>
	<div class="row m-0">
		@foreach($infographs as $infograph)
		@include('resources.infographs.card')
		@endforeach
	</div>
</div>

<div class="container mb-6">
	@include('components.sections.feedback')
	@include('components.sections.youtube')
</div>

@include('resources.infographs.show')
@include('components.overlays.subscribe.paper-plane')
@endsection

@push('scripts')
@include('components.addthis')
<script>
$('.thumbnail').hover(function() {
    $(this).siblings().addClass('opacity-6');
}, function() {
    $(this).siblings().removeClass('opacity-6');
});

$('#infograph-modal').on('show.bs.modal', function (e) {
	let $modal = $(e.target);
	let $infograph = $(e.relatedTarget);
	let downloads = $infograph.attr('data-downloads');

	$modal.find('.review').attr('data-url', $infograph.attr('data-review-url'));
	$modal.find('.url').attr('href', $infograph.attr('data-url'));
	$modal.find('.preview').attr('src', $infograph.attr('data-image'));
	$modal.find('.name').text($infograph.attr('data-name'));
	$modal.find('.description').text($infograph.attr('data-description'));
	$modal.find('.type').text($infograph.attr('data-type'));

	if (downloads > 10) {
		$('#downloads-count span').text(downloads);
		$('#downloads-count').show();
	} else {
		$('#downloads-count').hide();		
	}
});

$('#infograph-modal').on('hidden.bs.modal', function (e) {
  let $modal = $(e.target);

  $modal.find('.preview').attr('src', '');
  $modal.find('.review').removeClass('voted text-blue text-light').addClass('text-grey');
  $modal.find('.infograph-feedback').text('Help us improve by sending us your feedback.');
});

$('.infograph-type-btn').on('click', function() {
	let $button = $(this);
	let type = $button.attr('data-target');

	$button.removeClass('btn-teal-outline').addClass('btn-teal');
	$button.siblings().addClass('btn-teal-outline').removeClass('btn-teal');

	$('.thumbnail').hide();
	$(type).show();

	$('input#search-infograph').val('');
});

$('.review').click(function() {vote($(this))});

function vote($hand) {
  if (! $hand.hasClass('voted')) {
    $('.review').addClass('voted');
    $.post($hand.attr('data-url'), {liked: $hand.attr('data-value')})
      .done(function(message) {
        $('.review').removeClass('text-grey').addClass('voted');
        $hand.siblings().addClass('text-light');
        $hand.addClass('text-blue');
        $('.infograph-feedback').text('Thanks for your feedback!');
      })
      .fail(function(status) {
        let feedback = status.status == 429 ? 'Sorry, you\'re voting too many times. Try again later!' : 'Sorry, we couldn\'t save your feedback.';
        $('.infograph-feedback').text(feedback);
      });
  }
}

$('input#search-infograph').on('keyup', function() {
	let val = searchable($(this).val());

	if (val.length > 2) {
		console.log('Find infographs with: '+val);
		$('.infograph-card').each(function() {
			let $element = $(this);
			let name = searchable($element.attr('data-name'));

			if (name.includes(val)) {
				$element.show();
			} else {
				$element.hide();
			}

			$('.infograph-type-btn').addClass('btn-teal-outline').removeClass('btn-teal');
		});
	} else {
		console.log('Show all');
		$('.infograph-card').show();
		$('.infograph-type-btn').first().removeClass('btn-teal-outline').addClass('btn-teal');
	}
});

$("#subscribe-overlay").showAfter(5);
</script>
@endpush