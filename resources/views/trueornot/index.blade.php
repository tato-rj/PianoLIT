@extends('layouts.app', ['title' => 'PianoLIT Games: True or False'])

@push('header')
<link rel="stylesheet" type="text/css" href="{{asset('vendor/jTinder.css')}}">
<style type="text/css">
main {overflow: hidden !important;}
.tinderslide {
    position: relative;
    height: 320px;
    width: 100%;
    margin: 0 auto;
}

#endgame {
    position: relative;
    height: 200px;
    width: 100%;
    margin: 0 auto;
}

.tinderslide ul {
    margin: 0;
    position: relative;
    display: block;
    height: 100%;
    width: 100%;
}

.tinderslide li {
    display: block;
    width: 100%;
    height: 100%;
    overflow: hidden;
    position: absolute;
    top: 0;
    z-index: 2;
    left: 0;
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
    border-radius: 2rem;
}

.tinderslide li:nth-last-child(-n+2){
    -webkit-box-shadow: 0 .5rem 1rem rgba(0,0,0,.08)!important;
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.08)!important;
}

.tinderslide li:nth-last-child(-n+4){
    -webkit-box-shadow: 0 .5rem 1rem rgba(0,0,0,.04);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.04);
}

.tinderslide li:nth-last-child(-n+6){
    -webkit-box-shadow: 0 .5rem 1rem rgba(0,0,0,.02);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.02);
}
</style>
@endpush

@section('content')
<section class="container-fluid mb-5">
    @include('components.title', [
        'title' => 'True or False', 
        'subtitle' => 'Swipe right if you think the statemtent is true, left if you think it is false. Let\'s see how well you can do!'])

<div class="row">
	<div class="col-10 mx-auto mb-4" style="max-width: 380px">
		<div class="game-element" style="display: none;">
			<h5 class="text-grey text-center mb-3">Is this true?</h5>
		</div>
		<div class="mb-4">
			@include('trueornot.levels')
			
			@include('trueornot.cards', ['levels' => ['easy', 'difficult']])
		</div>
		@include('trueornot.feedback')

		@include('trueornot.endgame')

		@include('trueornot.arrows')
	</div>
</div>
</section>

<div class="container mb-6">
  @include('components.sections.youtube')
</div>

@include('components.games.results', ['button' => 'Go back'])
@endsection

@push('scripts')
@include('components.addthis')
<script type="text/javascript" src="{{asset('vendor/jquery.transform2d.js')}}"></script>
<script type="text/javascript" src="{{asset('vendor/jquery.jTinder.js')}}"></script>
<script type="text/javascript">
$(document).on('click', '#reload', function() {
    window.location = window.location.href.split("?")[0];
});

function submit(score, count) {
	$.get('{{route('true-or-false.feedback')}}', {score: score, count: count}, function(response) {
		$('#game-feedback').html(response);
		$('#game-results').modal('show');
	}).fail(function(response) {
        console.log(response);
	});
}

$('#level button.level').on('click', function() {
	$('#level button.level').removeClass('btn-teal').addClass('btn-light text-grey');
	$(this).removeClass('btn-light text-grey').addClass('btn-teal');
});

$('#start-game').on('click', function() {
	let game = $('#level button.btn-teal').attr('data-target');

	if (! game) {
		alert('Please select your level');
	} else {
		$('#level').fadeOut(function() {
			$(this).remove();
		});

		$(game).show(function() {
			$(this).find('li').each(function(index, element) {
				let $card = $(this);
				setTimeout(function() {
					$card.css('opacity', 1);
				}, 100 * index);
			});
			startGame(game);
		});
	}
});
</script>
<script type="text/javascript">
var score = cardsNum = 0;

function showFeedback($item, result, game) {
	let answer = result ? 'correct' : 'wrong';
	let color = result ? 'green' : 'red';
	let direction = result ? '-100px' : '240px';
	let $feedback = $('.feedback').clone();

	$feedback.find('h1').addClass('text-'+color+' border-'+color).text(answer);
	$feedback.appendTo($(game)).fadeIn('fast', function(){
		let $card = $(this);
		$card.animate({
			'top': direction,
			'opacity': 0
		}, {
			duration: 400,
			easing: 'swing',
			complete: function() {
				$card.remove();
				if ($(game).find('li.card').length == 0) {
					submit(score, cardsNum);
					$('#game-results').on('hide.bs.modal', function (e) {
						$('.tinderslide, .game-element').hide();
						$('#endgame').show();
					})
				}
			}
		});
	});
}

function evaluate(choice, answer) {
	let result = choice == answer;

	if (result)
		score += 1;

	$('#final-score').text(score);
	return result;
}

function startGame(game) {
	cardsNum = $(game).attr('data-length');

	$(game).jTinder({
	    onDislike: function (item) {
	        showFeedback(item, evaluate('wrong', item.attr('data-answer')), game);
	        item.remove();
	    },
	    onLike: function (item) {
	        showFeedback(item, evaluate('correct', item.attr('data-answer')), game);
	        item.remove();
	    },
		animationRevertSpeed: 200,
		animationSpeed: 400,
		threshold: 1,
		likeSelector: '.like',
		dislikeSelector: '.dislike'
	});

	$('.game-element').fadeIn();
}

$('.card').each(function() {
	var num = Math.floor(Math.random()*5) + 1;
	num *= Math.floor(Math.random()*2) == 1 ? 1 : -1;

	$(this).css('transform', 'rotate('+num+'deg)');
});
</script>
@endpush
