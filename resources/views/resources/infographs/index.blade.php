@extends('layouts.app', [
	'title' => 'Infographs | ' . config('app.name'),
	'noclicks' => true,
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
.card-overlay {
	background: rgb(0,0,0);
	background: -moz-linear-gradient(0deg, rgba(0,0,0,0.7514356084230567) 0%, rgba(0,212,255,0) 87%);
	background: -webkit-linear-gradient(0deg, rgba(0,0,0,0.7514356084230567) 0%, rgba(0,212,255,0) 87%);
	background: linear-gradient(0deg, rgba(0,0,0,0.7514356084230567) 0%, rgba(0,212,255,0) 87%);
	filter: progid:DXImageTransform.Microsoft.gradient(startColorstr="#000000",endColorstr="#00d4ff",GradientType=1);
	bottom: 0; 
	right: 0;
}
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
		@foreach($topics as $topic)
			<button data-target=".thumbnail-{{$topic->slug}}" class="infograph-type-btn m-1 btn border-0 rounded-pill btn-teal-outline">{{$topic->name}}</button>
		@endforeach
	</div>
	<div class="row m-0">
		@foreach($infographs as $infograph)
		@include('resources.infographs.card')
		@endforeach
	</div>
</div>


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
	let $topicsContainer = $modal.find('.topics');
	let downloads = $infograph.attr('data-downloads');
	let topics = JSON.parse($infograph.attr('data-topics'));

	$modal.find('.review').attr('data-url', $infograph.attr('data-review-url'));

	$modal.find('.preview').attr('src', $infograph.attr('data-image'));
	$modal.find('.name').text($infograph.attr('data-name'));
	$modal.find('.description').text($infograph.attr('data-description'));

	$topicsContainer.html('');

	topics.forEach(function(topic) {
		$topicsContainer.append('<span class="badge type badge-light mb-2 mr-2">'+topic+'</span>');
	});

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