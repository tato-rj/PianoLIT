@extends('layouts.app', ['title' => 'PianoLIT Quizzes'])

@push('header')
  <!-- END Twitter Card -->
  <meta name="apple-itunes-app" content="app-id=00000000" />
@endpush

@section('content')
<section class="container mb-5">
    @include('components.title', [
        'title' => 'Quizzes', 
        'subtitle' => 'Test your knowledge and learn cool new facts about music'])

	<div class="row">
		<div class="col-lg-10 col-md-9 col-12">
        <div class="row">
          @each('components.quiz.cards.large', $quizzes, 'quiz')
        </div>
    </div>
    <div class="col-lg-2 col-md-3 col-12">
      <div class="mb-2">
        <p class="text-muted mb-1 pb-1 border-bottom"><strong>LEVELS</strong></p>
        <div>
          @foreach($levels as $level)
          <a href="{{route('quizzes.index', ['level' => $level->slug])}}" class="btn btn-{{request('level') == $level->slug ? 'teal' : 'light'}} m-1 btn-sm text-muted text-truncate">{{ucfirst($level->name)}}</a>
          @endforeach
        </div>
      </div>

      <div>
        <p class="text-muted mb-1 pb-1 border-bottom"><strong>TOPICS</strong></p>
        <div>
          @foreach($topics as $topic)
          <a href="{{route('quizzes.index', ['topics' => $topic->slug])}}" class="btn btn-{{request('topics') == $topic->slug ? 'teal' : 'light'}} m-1 btn-sm text-muted">{{ucfirst($topic->name)}}</a>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</section>
@endsection

@push('scripts')
<script type="text/javascript">
$('.card-title').each(function() {
  $clamp(this, {clamp: 2});
});

$('.card-text').each(function() {
  $clamp(this, {clamp: 5});
});
</script>
@endpush