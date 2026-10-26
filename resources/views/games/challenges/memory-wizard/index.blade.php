@extends('games.challenges.layout', ['title' => $settings->gameName()])

@push('header')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Music&display=swap" rel="stylesheet">
<link href="{{ mix('css/musicgames.css') }}" rel="stylesheet">

<script>
  window.__challengeOptions = @json($settings->browserOptions());
  window.__clefUrls = {
    treble: "{{ asset('images/games/clefs/treble-clef.svg') }}",
    bass:   "{{ asset('images/games/clefs/bass-clef.svg') }}",
    alto:   "{{ asset('images/games/clefs/alto-clef.svg') }}",
    tenor:  "{{ asset('images/games/clefs/tenor-clef.svg') }}",
  };
</script>

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

    <div class="col-lg-8 col-md-10 col-11 mx-auto text-center">
      @include('games.challenges.components.instructions', ['instructionContent' => 'Press Play when you’re ready.'])
    </div>

    <div class="col-11 mx-auto mb-3 position-relative">
      <div id="staff-wrapper" class="text-center mx-auto position-relative">
        @include('games.challenges.components.accidentals')
        <div id="staff"></div>
        @include('games.challenges.components.feedback')
      </div>
    </div>

    <div class="col-lg-6 col-md-8 col-11 mx-auto">
      @include('games.challenges.components.controls', ['type' => 'play-check'])
      @include('games.challenges.memory-wizard.settings')
      @include('games.challenges.components.leaderboard.show')
    </div>
  </div>
</section>

@include('games.challenges.components.results.overlay')
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tone@14.8.49/build/Tone.js"></script>
<script src="{{ mix('js/games/memorywizard.js') }}"></script>
<script>
$(function () {
  $('[data-toggle="tooltip"]').tooltip()
});
</script>
@endpush
