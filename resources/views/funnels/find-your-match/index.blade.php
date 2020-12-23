@extends('layouts.app', [
	'title' => 'Discover the right pieces for you'])

@push('header')
<style type="text/css">
	.unselected-answer {
		opacity: .4;
	}

	.questions-container:not(:last-child) {
		margin-bottom: 3rem!important;
	}

	.audio-control {
	    bottom: 8px;
	    left: 50%;
	    transform: translateX(-50%);
	}
</style>
@endpush

@section('content')
<section class="container py-4 mb-5">
  <div class="row mb-5">
    <div class="col-lg-8 col-md-12 mx-auto">
      {{-- @include('webapp.layouts.header', ['title' => 'Find your match', 'subtitle' => 'Take this quick tour to find the perfect piece for you']) --}}
		@pagetitle([
			'title' => 'Find your match', 
			'subtitle' => 'Take this quick tour to find the perfect piece for you'])

     	@include('funnels.find-your-match.components.quiz')
    </div>
  </div>

  <div class="row">
  	<div class="col-12 text-center">
  		<h6>All set?</h6>
  		<p class="text-muted">Click below to submit your answers and find your best match</p>
		@button([
			'label' => 'Find my best match',
			'styles' => [
				'shadow' => true,
				'size' => 'wide',
				'theme' => 'primary',
			],
			'id' => 'find-button'
		])

  		<form method="GET" action="{{route('funnels.find-your-match.results')}}" id="find-form">
  			<input type="hidden" name="input">
  		</form>
  	</div>
  </div>
</section>
@endsection

@push('scripts')
@include('components.addthis')
<script type="text/javascript">
var player;
var query = [];

$('#quiz .answer-card').on('click', function(e) {
	if ($(e.target).is('button'))
		return;

	let $answer = $(this);

	$answer.closest('.questions')
		   .find('.answer-card')
		   .removeClass('selected-answer shadow-center')
		   .addClass('unselected-answer');

	$answer.addClass('selected-answer shadow-center')
		   .removeClass('unselected-answer');

	resetAudio();
	getSearchTerms();
});

$('#find-button').click(function() {
	let missingAnswers = $('.questions-container').length - query.length;

	if (missingAnswers > 0) {
		alert('You have ' + missingAnswers + ' left to answer');
	} else {
		$(this).addLoader();
		$('#find-form').find('input[name="input"]').val(query);
		$('#find-form').submit();
	}
});

$('.audio-control button').on('click', function() {
	let $btn = $(this);

	$btn.hide();
	$btn.siblings('button').show();

	if (isPlaying()) {
		resetAudio();
	} else {
		play($btn.siblings('audio'));
	}
});

function play($audio)
{
	player = $audio.get(0);
	player.play();
}

function resetAudio()
{
	if (player) {
		player.pause();
		player = null;
		$('button[data-action="pause"]').hide();
		$('button[data-action="play"]').show();
	}
}

function isPlaying()
{
	return player ? true : false;
}

function getSearchTerms()
{
	query = $('.answer-card.selected-answer').attrToArray('data-query');
}
</script>
@endpush