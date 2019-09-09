@extends('layouts.app', ['title' => 'PianoLIT Quizzes'])

@push('header')
<link rel="stylesheet" type="text/css" href="{{asset('vendor/jTinder.css')}}">
<style type="text/css">
main {overflow: hidden !important;}
</style>
@endpush

@section('content')
<section class="container-fluid mb-5">
    @include('components.title', [
        'title' => 'True or Not?', 
        'subtitle' => 'Have fun with our riddles and puzzles. How many can you solve?'])

<div class="row">
	<div class="col-6 mx-auto my-4">
		<div id="tinderslide">
			<div class="absolute-center d-flex flex-center w-100 h-100" style="z-index: 1"><button class="btn btn-primary">Play again</button></div>
		    <ul class="list-flat">

		    	@foreach($questions as $question => $answer)
		        <li class="pane d-flex p-4 flex-center bg-white" data-color="{{$answer ? 'green' : 'red'}}" data-answer="{{$answer ? 'correct' : 'wrong'}}" style="cursor: grab"><div>{{$question}}</div></li>
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
let options = {
      duration: 800,
      easing: 'swing'
	};
function showFeedback(answer, color) {
	let $feedback = $('.feedback').clone();
	$feedback.find('h1').addClass('text-'+color).text(answer);
	$feedback.appendTo($('#tinderslide')).fadeIn('fast', function(){
		let $card = $(this);
		$card.animate({
			'top': '-100px',
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

$("#tinderslide").jTinder({
    onDislike: function (item) {
        showFeedback(item.attr('data-answer'), item.attr('data-color'));
        item.remove();
    },
    onLike: function (item) {
        showFeedback(item.attr('data-answer'), item.attr('data-color'));
        item.remove();
    },
	animationRevertSpeed: 200,
	animationSpeed: 400,
	threshold: 1,
	likeSelector: '.like',
	dislikeSelector: '.dislike'
});

$('.pane').each(function() {
	var num = Math.floor(Math.random()*5) + 1;
	num *= Math.floor(Math.random()*2) == 1 ? 1 : -1;

	$(this).css('transform', 'rotate('+num+'deg)');
});
</script>
@endpush
