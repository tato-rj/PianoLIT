@extends('layouts.app', ['title' => 'PianoLIT Quizzes'])

@push('header')

@endpush

@section('content')
<section class="container-fluid mb-5">
    @include('components.title', [
        'title' => 'Riddles', 
        'subtitle' => 'Have fun with our riddles and puzzles. How many can you solve?'])

  @foreach($riddles as $bg => $group)
  <div class="row" style="background: {{$bg}};">
    @foreach($group as $riddle)
      <div class="col-lg-7 col-md-8 col-10 mx-auto text-center mt-6 mb-8 cursor-pointer riddle">
        <div class="position-relative mb-5">
          <img src="{{asset('images/riddles/'.str_slug($riddle).'.svg')}}" class="w-100 t-2" style="max-width: 460px">
          <h4 class="text-primary position-absolute t-2" style="opacity: 0; top: 50%; left: 50%; transform: translate(-50%, -50%);">{{$riddle}}</h4>
        </div>
        <div class="text-muted answer" data-hidden="Tap to reveal the answer" data-visible="Back to the puzzle">Tap to reveal the answer</div>
      </div>     
    @endforeach
  </div>    
  @endforeach

</section>

<div class="container mb-6">
  @include('components.sections.youtube')
</div>

@include('components.overlays.subscribe.paper-plane')
@endsection

@push('scripts')
<script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=ra-5c872ce214693180"></script>
<script type="text/javascript">
$("#subscribe-overlay").showAfter(10);

$('.riddle').on('click', function() {
  let $riddle = $(this);
  let $answer = $riddle.find('.answer');
  let imageIsVisible = $riddle.find('img').css('opacity') == 1;

  if (imageIsVisible) {
    $riddle.find('img').css('opacity', 0);
    $riddle.find('h4').css('opacity', 1);
    $answer.text($answer.attr('data-visible'));
  } else {
    $riddle.find('img').css('opacity', 1);
    $riddle.find('h4').css('opacity', 0);
    $answer.text($answer.attr('data-hidden'));
  }
});
</script>
@endpush