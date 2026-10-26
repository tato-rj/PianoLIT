<div
  id="{{$id ?? 'game-countdown'}}"
  class="game-countdown{{!empty($screen) ? ' game-countdown--screen' : ''}}"
  hidden>
  <div class="game-countdown__center">
    @isset($startLabel)
      <button
        type="button"
        class="game-countdown__button btn btn-primary btn-lg"
        data-game-countdown-start
        data-game-countdown-value>{{$startLabel}}</button>
    @else
      <span class="game-countdown__value" data-game-countdown-value hidden></span>
    @endisset
  </div>
</div>
