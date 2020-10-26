@extends('layouts.app', ['title' => 'PianoLIT Quizzes'])

@push('header')
@endpush

@section('content')
<section class="container mb-5">
   
  @pagetitle(['title' => 'Quizzes', 'subtitle' => 'Test your knowledge and learn cool new facts about music'])

  @component('components.display.layout', [
    'collection' => $quizzes,
    'topics' => $topics])

  @slot('items')
    @each('games.quizzes.components.cards.large', $quizzes, 'quiz')
  @endslot

  @slot('extra')
      <div>
        <div class="d-flex d-apart mb-1 pb-1 border-bottom">
          <p class="text-muted mb-0"><strong>LEVELS</strong></p>
          <a href="{{route('quizzes.index')}}" class="text-muted"><small>reset</small></a>
        </div>
        <div>
          @foreach($levels as $level)
          <a href="{{route('quizzes.index', request('level') == $level->slug ? null : ['level' => $level->slug])}}" class="btn btn-{{request('level') == $level->slug ? 'primary' : 'light'}} m-1 btn-sm text-muted text-truncate">{{ucfirst($level->name)}}</a>
          @endforeach
        </div>
      </div>
  @endslot

  @endcomponent

</section>

@popup(['view' => 'subscription'])
@endsection

@push('scripts')
<script type="text/javascript">
$('.card-title').clamp(2);
$('.card-text').clamp(randomBetween(3, 5));
</script>
@endpush