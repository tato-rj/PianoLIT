@extends('games.challenges.layout', ['title' => $settings->gameName()])

@push('header')
<link href="{{ mix('css/musicgames.css') }}" rel="stylesheet">

<script>
  window.__challengeOptions = @json($settings->browserOptions());
</script>

<style>
  .beat-hero-game {
    max-width: 760px;
    margin: 0 auto;
  }

  .sequence-dots {
    display: flex;
    justify-content: center;
    gap: 18px;
    min-height: 48px;
    margin-bottom: 1rem;
  }

  .sequence-dot {
    display: grid;
    place-items: center;
    width: 50px;
    height: 50px;
    border: transparent;
    {{-- border: 1px solid black; --}}
    border-radius: 14px;
    background: #fff;
    color: var(--beat-hero-ink);
    font-size: 1.25rem;
    font-weight: 900;
    line-height: 1;
    transition: background-color .15s ease, border-color .15s ease, box-shadow .15s ease, color .15s ease, transform .15s ease;
  }

  .sequence-dot__number {
    opacity: .4;
  }

  .sequence-dot.is-active {
    {{-- border-color: var(--beat-hero-gold); --}}
    background: var(--beat-hero-ink);
    color: #fff;
    {{-- box-shadow: 0 0 0 1px yellow; --}}
    transform: scale(1.08);
  }

  .sequence-dot.is-complete {
    border-color: #b9d8ee;
    background: #d9effd;
  }

  .sequence-dot.is-chosen {
    border-color: var(--beat-hero-green);
    background: var(--beat-hero-green);
    color: #fff;
    box-shadow: 0 0 0 5px rgba(88, 204, 2, .14);
  }

  .sequence-dot.is-wrong {
    border-color: var(--beat-hero-red);
    background: var(--beat-hero-red);
    color: #fff;
    box-shadow: 0 0 0 5px rgba(255, 75, 75, .14);
  }

  .sequence-dot__figure {
    display: block;
    width: 100%;
    height: 100%;
    padding: 3px;
  }

  .sequence-dot__figure svg {
    display: block;
    width: 100%;
    height: 100%;
  }

  .rhythm-card-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 36px;
    width: 100%;
  }

  .rhythm-card-grid .rhythm-card {
    flex: 0 1 calc((100% - 108px) / 4);
    max-width: 160px;
  }

  .rhythm-card:hover {
    transform: translateY(-2px);
  }

  .rhythm-card:active {
    transform: translateY(2px);
  }

  .rhythm-card:focus-visible {
    outline-offset: 3px;
  }

  .rhythm-card.is-previewing {
    border-color: var(--beat-hero-blue);
    background: #f3fbff;
  }

  .rhythm-card.is-correct {
    box-shadow: 0 0px 0 var(--beat-hero-green), 0 0 0 4px var(--beat-hero-green) !important;
  }

  .rhythm-card.is-wrong {
    box-shadow: 0 4px 0 var(--beat-hero-red), 0 0 0 4px var(--beat-hero-red) !important;
    animation: rhythmCardShake .35s ease;
  }

  .rhythm-card.is-sounding .rhythm-card__figure {
    transform: scale(1.07);
  }

  .rhythm-card__number {
    position: absolute;
    z-index: 2;
    top: 8px;
    right: 8px;
    display: none;
    place-items: center;
    min-width: 26px;
    padding: 0 6px;
    height: 26px;
    border-radius: 999px;
    background: var(--beat-hero-green);
    color: #fff;
    font-size: 14px;
    font-weight: 900;
  }

  .rhythm-card.is-correct .rhythm-card__number {
    display: grid;
  }

  .rhythm-card__figure {
    display: block;
    width: 100%;
    height: 100%;
    transition: transform .12s ease, opacity .15s ease;
  }

  .rhythm-card__figure svg {
    display: block;
    width: 100%;
    height: 100%;
    overflow: visible;
  }

  #play {
    {{-- min-height: 62px; --}}
    {{-- margin: 18px 0 12px; --}}
  }

  #play button {
    {{-- min-width: 132px; --}}
  }

  @keyframes rhythmCardShake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-6px); }
    75% { transform: translateX(6px); }
  }

  @media (max-width: 575.98px) {
    .beat-hero-game {
      width: calc(100vw - 24px);
      margin-left: calc(50% - 50vw + 12px);
    }

    .rhythm-card-grid {
      gap: 12px;
    }

    .rhythm-card-grid .rhythm-card {
      flex-basis: calc((100% - 24px) / 3);
      max-width: none;
    }

    .rhythm-card-grid[data-card-count="2"] .rhythm-card {
      flex-basis: calc((100% - 12px) / 2);
      max-width: 160px;
    }

    .rhythm-card__number {
      top: 8px;
      right: 8px;
      min-width: 21px;
      height: 21px;
      font-size: 12px;
    }

  }

  @media (prefers-reduced-motion: reduce) {
    .rhythm-card,
    .rhythm-card__figure,
    .sequence-dot {
      transition: none;
    }

    .rhythm-card.is-wrong {
      animation: none;
    }
  }
</style>
@endpush

@section('content')
@include('games.challenges.components.title')

<section id="page-wrapper" class="container prevent-select">
  <div class="row">
    <div class="col-lg-6 col-md-8 col-11 mx-auto text-center mb-2">
      @include('games.challenges.components.counter')
    </div>

    <div class="col-lg-8 col-md-10 col-11 mx-auto text-center position-relative mb-5">
      <div class="beat-hero-game w-100 mx-auto">
        <div id="sequence-dots" class="sequence-dots" aria-label="Rhythm sequence progress"></div>

        @include('games.challenges.components.instructions', ['instructionId' => 'sequence-status'])

        <div id="rhythm-card-grid" class="rhythm-card-grid" aria-label="Rhythm cards"></div>

        {{-- @include('games.challenges.components.play') --}}
      </div>
    </div>

    <div class="col-lg-6 col-md-8 col-11 mx-auto">
      @include('games.challenges.components.controls', ['type' => 'play'])
      @include('games.challenges.beat-hero.settings')
      @include('games.challenges.components.leaderboard.show')
      @include('games.challenges.components.preferences')
    </div>
  </div>
</section>

@include('games.challenges.components.results.overlay')
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tone@14.8.49/build/Tone.js"></script>
@endpush
