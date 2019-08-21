@extends('layouts.app', ['title' => $quiz->title . ' | PianoLIT Quiz',
	'shareable' => [
		'keywords' => '',
		'title' => 'QUIZ: ' . $quiz->title,
		'description' => $quiz->description,
		'thumbnail' => $quiz->thumbnail_image(),
		'created_at' => $quiz->created_at->format(DateTime::ISO8601),
		'updated_at' => $quiz->updated_at->format(DateTime::ISO8601)
	]])

@push('header')
<style type="text/css">
.blog-font p, .blog-font h4, .blog-font iframe {
	margin-bottom: 1.75rem;
}
.question-overlay {
	width: 100%; 
	height: 100%; 
	position: absolute; 
	top: 0; 
	left: 0; 
	display: none; 
	background: rgba(255,255,255,0.5);
    z-index: 100;
}
</style>
<script async defer data-pin-hover="true" data-pin-tall="true" src="//assets.pinterest.com/js/pinit.js"></script>
<script>
    window.app = <?php echo json_encode([
        'csrfToken' => csrf_token(),
        'page_url' => url()->current(),
        'page_id' => $quiz->slug
    ]); ?>
</script>
@endpush

@section('content')
@include('components.progressbar')
<section id="quiz" class="container mb-5">
	<div class="row mb-6" id="main-content">
		<div class="col-lg-8 col-12 mx-auto">
			@if(! empty($preview))
			<div class="alert alert-warning" role="alert">
			  <i class="fas fa-exclamation-triangle mr-2"></i>This quiz is <u>not published</u>. Only admins can see this page.
			</div>
			@endif
			<div class="mb-4">
				<h1 class="mb-4">{{$quiz->title}}</h1>
				<p class="text-muted blog-font">{{$quiz->description}}</p>
				<div class="d-apart text-muted">
					<p><small>{{$quiz->created_at->toFormattedDateString()}} &bull; {{count($quiz->questions)}} questions</small></p>
					<p><small><i class="fas fa-eye mr-2"></i>{{$quiz->views}}</small></p>
				</div>
				<figure class="figure w-100">
					<img src="{{$quiz->cover_image()}}" class="figure-img img-fluid rounded w-100">
				</figure>
				<div class="border-bottom mb-3 pb-3 text-center">
					<p class="m-0 text-muted">Want a heads up when a new quiz comes out? <span class="text-blue cursor-pointer btn-subscribe">Subscribe here</span></p>
				</div>
			</div>
			<div class="bg-light rounded px-5 py-4 text-center mt-6 mb-7">
				<p>This quiz has <strong>{{count($quiz->questions)}}</strong> questions and it shouldn't take more than <u>{{$quiz->duration}}</u> to complete.</p>
				<h5 class="mb-3">Are you ready to start?</h5>
				<button class="btn btn-blue btn-wide" id="start-quiz"><strong>Yes, let's do this!</strong></button>
			</div>
			<div id="quiz-content" class="blog-font mb-6" style="display: none;">
				@include('quizzes.questions')
			</div>

			<div class="mb-5 d-apart">
				<div>
					<div class="d-inline-block align-middle mr-3">
						<img src="{{asset('images/brand/app-icon.svg')}}" class="rounded-circle" width="50">
					</div>
					<div class="d-inline-block align-middle" style="max-width: 320px; line-height: 1.12">
						<div style="font-size: .9rem"><strong>PianoLIT Team</strong></div>
						<div class="text-muted"><small>Where pianists discover new pieces and find inspiration to play only what they love.</small></div>
					</div>
				</div>
				<div class="text-right">
					<button  class="btn btn-primary-outline btn-sm btn-subscribe">Subscribe</button>
				</div>
			</div>
		</div>
	</div>

	<div class="row mb-6">
		<div class="col-12 mb-4">
			<div><strong>OTHER QUIZZES YOU MIGHT LIKE</strong></div>
		</div>
		@each('components.quiz.cards.small', $suggestions, 'suggestion')
	</div>
</section>

@include('quizzes.results')
@endsection

@push('scripts')
<script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-5c872ce214693180"></script>
<script type="text/javascript">
$('.card-title').each(function() {
  $clamp(this, {clamp: 2});
});

$('.btn-subscribe').on('click', function() {
	$("#subscribe-overlay").fadeIn('fast');
});
</script>
<script type="text/javascript">
var answers = [];

$('#start-quiz').click(function() {
	$(this).parent().remove();
	$('#quiz-content').show();
	showScrollProgressBar($('#main-content'));
});

$('.quiz-answers button').on('click', function() {
	$button = $(this);
	$parent = $button.parent();
	
	stopAll();
	
	$($button.attr('data-overlay')).show();

	$button.addClass('selected');
	
	getAnswers();

	console.log(answers);

	$parent.find('button[correct]').toggleClass('list-group-item-action alert-green').find('.fas').show();

	if (! $button.is('[correct]'))
		$button.toggleClass('list-group-item-action alert-red').find('.fas').show();

	if (! answers.includes(null))
		submit();
});

function getAnswers()
{
	answers = [];
	
	$('.quiz-answers').each(function(index) {
		selection = $(this).find('button.selected').index();
		answers.push(selection >= 0 ? selection : null);
	});
}

function submit() {
	$.get('{{route('quizzes.feedback', $quiz->slug)}}', {answers: answers}, function(response) {
		$('#quiz-feedback').html(response);
		$('#quiz-results').modal('show');
	}).fail(function(response) {
        console.log(response);
	});
}

function stopAll() {
    var media = document.getElementsByClassName('audio'),
        i = media.length;

    while (i--) {
        media[i].pause();
        media[i].currentTime = 0;
    }
}
</script>
@endpush