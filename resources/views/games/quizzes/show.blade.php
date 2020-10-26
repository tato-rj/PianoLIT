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
<script async defer data-pin-hover="true" data-pin-tall="true" src="//assets.pinterest.com/js/pinit.js"></script>
@endpush

@section('content')

@progressbar

<section id="quiz" data-url="{{route('quizzes.feedback', $quiz->slug)}}" class="container mb-5">
	<div class="row mb-6" id="main-content">
		<div class="col-lg-8 col-12 mx-auto">
			@if(! empty($preview))
			<div class="alert alert-warning" role="alert">
			  <i class="fas fa-exclamation-triangle mr-2"></i>This quiz is <u>not published</u>. Only admins can see this page.
			</div>
			@endif
			<div class="mb-4">
				@topics(['model' => $quiz])
				
				<h1 class="mb-4">QUIZ: {{$quiz->title}}</h1>
				<p class="text-muted blog-font">{{$quiz->description}}</p>
				<div class="d-apart text-muted">
					<p><small>{{$quiz->created_at->toFormattedDateString()}} &bull; {{count($quiz->questions)}} questions</small></p>
					<p><small><i class="fas fa-eye mr-2"></i>{{$quiz->views}}</small></p>
				</div>
				<figure class="figure w-100">
					<img src="{{$quiz->cover_image()}}" class="figure-img img-fluid rounded w-100">
				</figure>
				<div class="border-bottom mb-4 pb-3 text-center">
					<p class="m-0 text-muted">Want a heads up when a new quiz comes out? <span class="text-blue cursor-pointer btn-subscribe">Subscribe here</span></p>
				</div>

				@include('games.quizzes.level')
			</div>
			<div class="bg-light rounded px-5 py-4 text-center mt-5 mb-7">
				<p>This quiz has <strong>{{count($quiz->questions)}}</strong> questions and it shouldn't take more than <u>{{$quiz->duration}}</u> to complete.</p>
				<h5 class="mb-3">Are you ready to start?</h5>
				<button class="btn btn-primary btn-wide" id="start-quiz"><strong>Yes, let's do this!</strong></button>
			</div>
			<div id="quiz-content" class="blog-font mb-6" style="display: none;">
				@include('games.quizzes.questions')
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

	@include('components.display.suggestions', [
		'title' => 'OTHER QUIZZES YOU MIGHT LIKE',
		'card' => 'games.quizzes.components.cards.small',
		'collection' => $suggestions])

</section>

<div class="container mb-6">
	@include('components.sections.youtube')
</div>
@include('games.components.results', ['button' => 'Review my answers'])
@endsection

@push('scripts')
@addthis
<script type="text/javascript" src="{{asset('js/views/quizzes.js')}}"></script>
<script type="text/javascript">
$('.card-title').clamp(2);
</script>
@endpush