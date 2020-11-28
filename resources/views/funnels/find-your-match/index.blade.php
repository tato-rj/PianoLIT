@extends('layouts.app', [
	'raw' => true,
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
<section class="container py-4">
  <div class="row">
    <div class="col-lg-8 col-md-12 mx-auto">
      @include('webapp.layouts.header', ['title' => 'Find your match', 'subtitle' => 'Take this quick tour to find the perfect piece for you'])

      @include('funnels.find-your-match.components.quiz')
    </div>
  </div>
</section>
@endsection

@push('scripts')
@include('components.addthis')
<script type="text/javascript">
var player;

$('#quiz .answer-trigger').on('click', function() {
	let $btn = $(this);

	$btn.closest('.questions').find('.answer-card').removeClass('selected-answer shadow-center').addClass('unselected-answer');
	$btn.closest('.answer-card').addClass('selected-answer shadow-center').removeClass('unselected-answer');

	resetAudio();
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
</script>
@endpush