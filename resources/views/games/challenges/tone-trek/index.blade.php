@extends('games.challenges.layout', ['title' => $settings->gameName()])

@push('header')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Music&display=swap" rel="stylesheet">
<link href="{{ mix('css/musicgames.css') }}" rel="stylesheet">

<script>
  window.__challengeOptions = @json($settings->browserOptions());
</script>

<style>
.table {
  width: 320px;
  max-width: 100%;
}

td {
  height: 79.75px;
  position: relative;
}

.initial-block {
  background: #ffe54c !important;
  box-shadow: 0 0 0 1px black inset !important;
  {{-- border: 1px solid #ffe54c !important; --}}
}

.block {
  background: lightyellow !important;
  box-shadow: 0 0 0 1px black inset !important;
  {{-- border: 1px solid #ffe54c !important; --}}
}

.interval-block {
  background: black !important;
  {{-- border: 1px solid black !important; --}}
  color: white !important;
}

.initial-block div, .interval-block div, .block div {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.initial-block div span {
  position: absolute;
  width: 100%;
  top: 8px;
  left: 0;
  text-align: center;
  font-size: 60%;
  font-weight: bold;
  opacity: .4;
}

.initial-block div i {
  position: absolute;
  width: 100%;
  bottom: 6px;
  left: 0;
  text-align: center;
  font-size: 1rem;

}

.block-cover {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: #f7f7f7;
  background-image:  repeating-linear-gradient(45deg, #fff 25%, transparent 25%, transparent 75%, #fff 75%, #fff), repeating-linear-gradient(45deg, #fff 25%, #f7f7f7 25%, #f7f7f7 75%, #fff 75%, #fff);
  background-position: 0 0, 10px 10px;
  background-size: 20px 20px;
  z-index: 100;
  border: 1px solid white;
}

.initial-block input, .block input {
  width: 100%;
  height: 100%;
  border: none;
  text-align: center;
  background: transparent;
  color: black !important;
  font-weight: bold;
  font-size: 1.4rem;
  z-index: 2;
}

.block-correct, .block-wrong {
  position: absolute;
  top: 4px;
  right: 4px;
  color: white;
}

.block span:not(.block-correct):not(.block-wrong) {
  position: absolute;
  z-index: 1;
  font-size: 80%;
  font-weight: bold;
  opacity: .2;
}
.interval-block button {
  font-size: 76%;
}
.interval-block span[interval] {
  font-size: 1.4rem;
  font-weight: bold;
}
.interval-block span[direction] {
  font-size: .6rem;
}

.interval-block > div:not(.block-cover) {
  display: flex;
  flex-direction: column;
  cursor: pointer;
}

.interval-block button {
  background: transparent;
  border: none;
  color: yellow;
}

</style>
@endpush

@section('content')
@include('games.challenges.components.title')
<section id="page-wrapper" class="container prevent-select">
  <div class="row">
    <div class="col-lg-6 col-md-8 col-11 mx-auto text-center mb-2">
      @include('games.challenges.components.counter')
      @include('games.challenges.components.timer')
    </div>

    <div class="col-lg-8 col-md-10 col-11 mx-auto text-center">
      @include('games.challenges.components.instructions', ['instructionContent' => 'Tap an interval to hear it, then add the next note.'])
    </div>

    <div class="col-11 mx-auto mb-3 position-relative">
      <div id="blocks-wrapper" class="text-center mx-auto position-relative">
        <div id="blocks" class="my-4">
          @include('games.challenges.tone-trek.grid')
        </div>
        @include('games.challenges.components.feedback')
      </div>
    </div>

    <div class="col-lg-6 col-md-8 col-11 mx-auto">
      @include('games.challenges.components.controls')
      @include('games.challenges.tone-trek.settings')
      @include('games.challenges.components.leaderboard.show')
      @include('games.challenges.components.preferences')
    </div>
  </div>
</section>

@include('games.challenges.components.results.overlay')
@include('games.challenges.components.keyboard', ['type' => 'notes'])
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tone@14.8.49/build/Tone.js"></script>
<script src="{{ mix('js/games/tonetrek.js') }}"></script>
@endpush
