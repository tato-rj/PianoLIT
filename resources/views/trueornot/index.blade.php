@extends('layouts.app', ['title' => 'PianoLIT True or False?'])

@push('header')
<link rel="stylesheet" type="text/css" href="{{asset('vendor/jTinder.css')}}">
<style type="text/css">
main {overflow: hidden !important;}
</style>
@endpush

@section('content')
<section class="container-fluid mb-5">
    @include('components.title', [
        'title' => 'True or False', 
        'subtitle' => 'Have fun with our riddles and puzzles. How many can you solve?'])

<div class="row">
	<div class="col-lg-6 col-10 mx-auto mb-5 mt-4">
		<div id="tinderslide">
			<div id="instructions">
				<div class="mb-4">
					<div class="bg-light rounded-pill px-5 py-4 w-100 mb-3 d-flex">
						<h1 class="m-0 text-muted" style="opacity: .12"><strong>1</strong></h1>
						<div class="flex-grow ml-4">
							<p class="m-0 text-muted">Swipe <strong>RIGHT</strong> if you think the statement is <strong class="text-green">TRUE</strong></p>
						</div>
					</div>
					<div class="bg-light rounded-pill px-5 py-4 w-100 d-flex">
						<h1 class="m-0 text-muted" style="opacity: .12"><strong>2</strong></h1>
						<div class="flex-grow ml-4">
							<p class="m-0 text-muted">Swipe <strong>LEFT</strong> if you think the statement is <strong class="text-red">FALSE</strong></p>
						</div>
					</div>
				</div>
				<div class="text-center">
					<p class="text-grey">Are you ready to play?</p>
					<button id="start-game" class="btn btn-wide btn-primary">Let's do this!</button>
				</div>
			</div>
		    <ul class="list-flat" style="display: none;">
		    	@foreach($statements as $statement => $answer)
		        <li class="card d-flex p-4 flex-center bg-white" data-answer="{{$answer ? 'correct' : 'wrong'}}" style="opacity: 0; cursor: grab; border: 2.4rem solid {{randval($colors)}}"><h5 class="m-0">{!! $statement !!}</h5></li>
		        @endforeach
		    </ul>
		    <div class="feedback z-10 w-100 h-100 absolute-center" style="display: none; cursor: grab">
		    	<div class="d-flex flex-center w-100 h-100 ">
			    	<strong><h1 class="m-0 text-uppercase"></h1></strong>
			    </div>
		    </div>
		</div>
	</div>
</div>
</section>

<div class="container mb-6">
  @include('components.sections.youtube')
</div>

@include('components.games.results')
@endsection

@push('scripts')
<script type="text/javascript" src="{{asset('vendor/jquery.transform2d.js')}}"></script>
<script type="text/javascript" src="{{asset('vendor/jquery.jTinder.js')}}"></script>
<script type="text/javascript">
$('#start-game').on('click', function() {
	$('#instructions').fadeOut(function() {
		$(this).remove();
	});
	$('#tinderslide ul').show(function() {
		$(this).find('li').each(function(index, element) {
			let $card = $(this);
			setTimeout(function() {
				$card.css('opacity', 1);
			}, 100 * index);
		});
		startGame();
	});
});
</script>
<script type="text/javascript">
var score = cardsNum = 0;
function showFeedback($item, result) {
	let answer = result ? 'correct' : 'wrong';
	let color = result ? 'green' : 'red';
	let direction = result ? '-100px' : '240px';
	let $feedback = $('.feedback').clone();

	$feedback.find('h1').addClass('text-'+color).text(answer);
	$feedback.appendTo($('#tinderslide')).fadeIn('fast', function(){
		let $card = $(this);
		$card.animate({
			'top': direction,
			'opacity': 0
		}, {
			duration: 400,
			easing: 'swing',
			complete: function() {
				$card.remove();
				if ($('li.card').length == 0) {
					$('#game-feedback h1').text(score);
					$('#game-feedback span').text(cardsNum);
					$('#game-results').modal('show');
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

function startGame() {
	cardsNum = $('#tinderslide li').length;
	$("#tinderslide").jTinder({
	    onDislike: function (item) {
	        showFeedback(item, evaluate('wrong', item.attr('data-answer')));
	        item.remove();
	    },
	    onLike: function (item) {
	        showFeedback(item, evaluate('correct', item.attr('data-answer')));
	        item.remove();
	    },
		animationRevertSpeed: 200,
		animationSpeed: 400,
		threshold: 1,
		likeSelector: '.like',
		dislikeSelector: '.dislike'
	});
}

$('.card').each(function() {
	var num = Math.floor(Math.random()*5) + 1;
	num *= Math.floor(Math.random()*2) == 1 ? 1 : -1;

	$(this).css('transform', 'rotate('+num+'deg)');
});
</script>
@endpush
