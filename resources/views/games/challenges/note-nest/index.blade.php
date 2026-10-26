@extends('games.challenges.layout', ['title' => $settings->gameName()])

@push('header')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Music&display=swap" rel="stylesheet">
<link href="{{ mix('css/musicgames.css') }}" rel="stylesheet">

<script>
  window.__challengeOptions = @json($settings->browserOptions());
  window.__microphoneSettings = @json(\App\Games\MicrophoneSettings::current());
  window.__clefUrls = {
    treble: "{{ asset('images/games/clefs/treble-clef.svg') }}",
    bass:   "{{ asset('images/games/clefs/bass-clef.svg') }}",
    alto:   "{{ asset('images/games/clefs/alto-clef.svg') }}",
    tenor:  "{{ asset('images/games/clefs/tenor-clef.svg') }}",
  };
</script>

<style>
  @keyframes floating {
  0% {
    box-shadow: 0 5px 15px 0px rgba(0,0,0,0.6);
    transform: translatey(0px);
  }
  50% {
    box-shadow: 0 25px 15px 0px rgba(0,0,0,0.2);
    transform: translatey(-20px);
  }
  100% {
    box-shadow: 0 5px 15px 0px rgba(0,0,0,0.6);
    transform: translatey(0px);
  }
}

#play-feedback {
  display: none;
}

#play-feedback.wrong {
  background: rgba(255,75,75,.15);
  color: #ff4b4b;
  opacity: 1;
}
</style>
@endpush

@section('content')
{{-- @include('games.challenges.components.pagetitle', [
  'title' => 'Intervals Challenge',
  'description' => 'Add the note that forms the indicated interval']) --}}

@include('games.challenges.components.title')
<section id="page-wrapper" class="container prevent-select">
  <div class="row">
    <div class="col-lg-6 col-md-8 col-11 mx-auto text-center">
      @include('games.challenges.components.counter')
      @include('games.challenges.components.timer')
    </div>

    <div class="col-11 mx-auto mb-3 position-relative">
      @include('games.challenges.components.piano.keyboard')
      <div id="staff-wrapper" class="text-center mx-auto position-relative">
        @include('games.challenges.components.accidentals')
        <div id="staff"></div>
        @include('games.challenges.components.feedback')
        @include('games.challenges.components.prompt')
        @if($settings->options('requirePlayedNote'))
        @include('games.challenges.components.play.feedback')
        @endif
      </div>
    </div>

    <div class="col-lg-6 col-md-8 col-11 mx-auto">
      @include('games.challenges.components.controls', ['instructions' => 'Add this note to the staff 👆'])
      @include('games.challenges.note-nest.settings')
      @include('games.challenges.components.leaderboard.show')
      @include('games.challenges.components.preferences')
    </div>
  </div>
</section>

@include('games.challenges.components.results.overlay')
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tone@14.8.49/build/Tone.js"></script>
<script src="{{ mix('js/games/notenest.js') }}"></script>
<script>
  $(function () {
  $('[data-toggle="tooltip"]').tooltip()
});
</script>
@endpush
