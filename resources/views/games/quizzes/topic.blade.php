@extends('layouts.app', ['title' => 'PianoLIT Quizzes'])

@section('content')
<section class="container mb-6">
	<div class="row">
    <div class="col-12 border-bottom mb-4 pb-4">
      <p class="text-muted mb-1"><small>QUIZZES ABOUT</small></p>
      <h2>{{ucfirst($topic->name)}}</h2>
    </div>
		<div class="col-lg-3 col-md-3 col-12">
      <p><strong>Other topics</strong></p>
      <div class="d-flex flex-wrap">
        @each('games.quizzes.components.topic', $topics, 'topic')
      </div>
    </div>
    <div class="col-lg-9 col-md-9 col-12">
      @each('games.quizzes.components.cards.horizontal', $quizzes, 'quiz')
    </div>

	</div>
</section>

<div class="container mb-6">
  @include('components.sections.youtube')
</div>
@endsection
