@extends('layouts.app', ['title' => 'PianoLIT Quizzes'])

@push('header')
@endpush

@section('content')
<section class="container mb-5">
    @include('components.title', [
        'title' => 'Quizzes', 
        'subtitle' => 'Test your knowledge and learn cool new facts about music'])

	<div class="row">
		<div class="col-lg-10 col-md-9 col-12 mb-4">
        <div class="row mb-4">
          @each('components.quiz.cards.large', $quizzes, 'quiz')
        </div>
        <div class="row">
          <div class="col-12 d-flex justify-content-center">
            {{ $quizzes->links() }}
          </div>
        </div>
    </div>
    <div class="col-lg-2 col-md-3 col-12" id="filters">
      <div class="mb-2">
        <p class="text-muted mb-1 pb-1 border-bottom"><strong>LEVELS</strong></p>
        <div>
          @foreach($levels as $level)
          <button data-href="{{route('quizzes.index', ['level' => $level->slug])}}" class="btn btn-{{request('level') == $level->slug ? 'teal' : 'light'}} m-1 btn-sm text-muted text-truncate">{{ucfirst($level->name)}}</button>
          @endforeach
        </div>
      </div>

      <div>
        <p class="text-muted mb-1 pb-1 border-bottom"><strong>TOPICS</strong></p>
        <div>
          @foreach($topics as $topic)
          <button data-href="{{route('quizzes.index', ['topics' => $topic->slug])}}" class="btn btn-{{request('topics') == $topic->slug ? 'teal' : 'light'}} m-1 btn-sm text-muted">{{ucfirst($topic->name)}}</button>
          @endforeach
        </div>
      </div>
    </div>
  </div>
</section>
@endsection

@push('scripts')
<script type="text/javascript">
$('#filters button').on('click', function() {
  return $(this).hasClass('btn-teal') ? 
    resetParams() : 
    goTo($(this).attr('data-href'));
});
$('.card-title').clamp(2);
$('.card-text').clamp(4);
</script>
@endpush