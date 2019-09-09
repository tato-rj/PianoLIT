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
	<div class="col-lg-6 col-10 mx-auto mb-6">
		<div class="text-center mb-4" style="max-width: 380px; margin: 0 auto;">
			<label class="text-teal m-0"><strong>IS THIS STATEMENT CORRECT?</strong></label>
			<div class="d-apart">
				<div>
					<label class="m-0 text-grey"><strong>NOPE</strong></label>
					<div><i class="fas fa-long-arrow-alt-left text-grey align-top"></i></div>
				</div>
				<div>
					<label class="m-0 text-grey"><strong>YES</strong></label>
					<div><i class="fas fa-long-arrow-alt-right text-grey align-top"></i></div>
				</div>
			</div>
		</div>
		<div id="tinderslide">
			<div class="absolute-center d-flex flex-center w-100 h-100" style="z-index: 1">
				<div class="text-center">
					<label class="text-grey m-0"><strong>YOU SCORED</strong></label>
					<h1 style="font-size: 4em" id="final-score" class="m-0"></h1>
					<h5 class="mb-3"><strong>out of {{count($statements)}}</strong></small></h5>
					<button class="btn btn-primary">Play again</button>
				</div>
			</div>
		    <ul class="list-flat">

		    	@foreach($statements as $statement => $answer)
		        <li class="card d-flex p-4 flex-center bg-white" data-answer="{{$answer ? 'correct' : 'wrong'}}" style="cursor: grab; border: 24px solid {{randval($colors)}}"><h5 class="m-0 font-weight-bold">{{$statement}}</h5></li>
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

@endsection

@push('scripts')
<script type="text/javascript" src="{{asset('vendor/jquery.transform2d.js')}}"></script>
<script type="text/javascript" src="{{asset('vendor/jquery.jTinder.js')}}"></script>
<script type="text/javascript">
var score = 0;
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
			}
		});
	});
}

function evaluate(choice, answer) {
	let result = choice == answer;

	console.log('This was a '+answer+' statement.');
	console.log(result);

	if (result)
		score += 1;

	$('#final-score').text(score);
	return result;
}

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

$('.card').each(function() {
	var num = Math.floor(Math.random()*5) + 1;
	num *= Math.floor(Math.random()*2) == 1 ? 1 : -1;

	$(this).css('transform', 'rotate('+num+'deg)');
});
</script>
@endpush
