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
  :root {
    --note-python-board-size: 368px;
    --note-python-border-width: 6px;
    --note-python-board-cols: 9;
    --note-python-cell-size: calc(var(--note-python-board-size) / var(--note-python-board-cols));
  }

  #board {
    display: inline-grid;
    grid-template-columns: repeat(var(--note-python-board-cols), var(--note-python-cell-size));
    grid-auto-rows: var(--note-python-cell-size);
    gap:0;
    width: calc(var(--note-python-board-size) + calc(var(--note-python-border-width) * 2));
    height: calc(var(--note-python-board-size) + calc(var(--note-python-border-width) * 2));
    background: white;
    border: var(--note-python-border-width) dashed rgba(0,0,0,0.05);
    border-radius: 24px;
  }

  #board.walled {
    border-color: firebrick;
    border-style: double;
  }

  @keyframes greenBoard {
    0% {border-color: rgba(0,0,0,0.1);}
    50% {border-color: #58cc02;}
    100% {border-color: rgba(0,0,0,0.1);}
  }

  @keyframes redBoard {
    0% {border-color: rgba(0,0,0,0.1);}
    50% {border-color: #ff4b4b;}
    100% {border-color: rgba(0,0,0,0.1);}
  }

  #board.board-correct-hit {
    animation: greenBoard 0.4s 6;
  }

  #board.board-wrong-hit {
    animation: redBoard 0.4s 6;
  }

  #restart {
    font-size: 3rem;
    z-index: 100;
  }

  #restart {
    display: none;
  }

  .board-cell {
    width: var(--note-python-cell-size);
    height: var(--note-python-cell-size);
    border: 1px dotted rgba(0,0,0,0.05);
  }

  .snake {
    position: relative;
    z-index: 1;
  }

  .snake-head {
    z-index: 2;
  }

  .snake-hop, .snake-segment {
    position: absolute;
    inset: 0;
    pointer-events: none;
  }

  .snake-segment {
    --segment-scale: .8;
    background: #ffe54c;
    border: .5px solid black;
    border-radius: 24px;
    transform: scale(var(--segment-scale));
    transform-origin: 50% 80%;
  }

  .snake-head .snake-segment {
    --segment-scale: 1;
    border-radius: 12px;
  }

  .snake-shadow {
    position: absolute;
    left: 18%;
    bottom: 0;
    width: 64%;
    height: 15%;
    border-radius: 50%;
    background: #38331e;
    opacity: .2;
    pointer-events: none;
  }

  .snake-hopping {
    animation: snake-hop-travel var(--hop-duration) linear both;
    animation-delay: var(--hop-delay);
  }

  .snake-hopping .snake-segment {
    animation: snake-hop-bounce var(--hop-duration) ease-in-out both;
    animation-delay: var(--hop-delay);
  }

  .snake-hopping .snake-shadow {
    animation: snake-hop-shadow var(--hop-duration) ease-in-out both;
    animation-delay: var(--hop-delay);
  }

  @keyframes snake-hop-travel {
    0%, 12% {
      transform: translate(calc(var(--hop-x) * var(--note-python-cell-size)), calc(var(--hop-y) * var(--note-python-cell-size)));
    }
    82%, 100% { transform: translate(0, 0); }
  }

  @keyframes snake-hop-bounce {
    0%, 100% { transform: scale(var(--segment-scale)); }
    12% { transform: scale(var(--segment-scale)) scale(1.14, .83); }
    46% { transform: translateY(-13px) scale(var(--segment-scale)) scale(.92, 1.12) rotate(-4deg); }
    82% { transform: scale(var(--segment-scale)) scale(1.18, .8) rotate(2deg); }
  }

  @keyframes snake-hop-shadow {
    0%, 100% { transform: scale(1); opacity: .2; }
    12%, 82% { transform: scale(1.15, .85); opacity: .26; }
    46% { transform: scale(.65, .7); opacity: .1; }
  }

  .snake-face {
    position: absolute;
    inset: 0;
    transform: rotate(var(--snake-facing, 0deg));
    pointer-events: none;
  }

  .snake-face-bob {
    position: absolute;
    inset: 0;
    transform-origin: 50% 35%;
    animation: snake-head-bob 840ms ease-in-out infinite;
    animation-delay: var(--snake-motion-time, 0ms);
  }

  .snake-eye {
    position: absolute;
    top: -9%;
    width: 34%;
    height: 36%;
    border: 1.5px solid #292820;
    border-radius: 50%;
    background: #fff;
    box-shadow: 0 2px 0 rgba(0, 0, 0, .12);
    overflow: hidden;
    transform-origin: 50% 90%;
    animation: snake-eye-wobble 610ms ease-in-out infinite;
    animation-delay: var(--snake-motion-time, 0ms);
  }

  .snake-eye-left {
    left: 9%;
  }

  .snake-eye-right {
    right: 9%;
    top: -13%;
    height: 40%;
    animation-duration: 730ms;
    animation-direction: reverse;
  }

  .snake-pupil {
    position: absolute;
    top: 27%;
    left: 29%;
    width: 43%;
    height: 43%;
    border-radius: 50%;
    background: #292820;
    animation: snake-pupil-rattle 470ms ease-in-out infinite;
    animation-delay: var(--snake-motion-time, 0ms);
  }

  .snake-pupil::after {
    content: "";
    position: absolute;
    top: 16%;
    right: 18%;
    width: 25%;
    height: 25%;
    border-radius: 50%;
    background: white;
  }

  .snake-eye-right .snake-pupil {
    animation-duration: 590ms;
    animation-direction: reverse;
  }

  @keyframes snake-head-bob {
    0%, 100% { transform: rotate(-3deg) translateY(0); }
    50% { transform: rotate(3deg) translateY(-1px); }
  }

  @keyframes snake-eye-wobble {
    0%, 100% { transform: rotate(-9deg) scale(1.04, .96); }
    35% { transform: translateY(-2px) rotate(8deg) scale(.94, 1.07); }
    65% { transform: translateY(1px) rotate(-4deg) scale(1.06, .94); }
  }

  @keyframes snake-pupil-rattle {
    0%, 100% { transform: translate(-35%, -25%); }
    30% { transform: translate(35%, 10%); }
    55% { transform: translate(10%, 40%); }
    80% { transform: translate(-25%, 15%); }
  }

  @media (prefers-reduced-motion: reduce) {
    .snake-face-bob, .snake-eye, .snake-pupil,
    .snake-hopping, .snake-hopping .snake-segment, .snake-hopping .snake-shadow {
      animation: none;
    }
  }

  .food {
    background: black;
    color: white;
    border-radius: 12px;
    border: .5px solid white;
  }

  .food-note {
    width: 100%;
    height: 100%;
    display: flex;
    justify-content: center;
    align-items: center;
    font-weight: bold;
  }

  #board.failed {
    background: rgba(255,0,0,0.2);
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
    <div class="col-12 mx-auto mb-2 position-relative">
      @include('games.challenges.components.instructions', ['instructionContent' => 'Swipe or use the arrows to control the snake'])

      <div id="board-wrapper" style="display: none" class="text-center mx-auto position-relative ">
          <div id="restart" class="position-absolute w-100 h-100">
            <div class="d-center w-100 h-100">
              <button onclick="window.location.reload();" type="button" class="btn btn-red btn-lg">@include('games.challenges.components.icon', ['icon' => 'arrow-rotate-right'])RESTART</button>
            </div>
          </div>

          <div id="board" class="my-2 mx-auto position-relative"></div>
      </div>
      @include('games.challenges.components.prompt')
      @include('games.challenges.components.feedback')
    </div>

    <div class="col-lg-6 col-md-8 col-11 mx-auto">
      @include('games.challenges.components.controls', ['type' => 'play', 'playLabel' => 'Start game'])
      @include('games.challenges.note-python.settings')
      @include('games.challenges.components.leaderboard.show')
      @include('games.challenges.components.preferences')
    </div>
  </div>
</section>

@include('games.challenges.components.results.overlay')
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tone@14.8.49/build/Tone.js"></script>
<script src="{{ mix('js/games/notepython.js') }}"></script>
@endpush
