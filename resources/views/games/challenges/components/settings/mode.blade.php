<div>
  <div class="text-center mb-2">
    <div class="btn-group mode-menu" role="group">
      <button data-target="#{{$modalID ?? null}}-rounds" type="button" class="btn btn-secondary btn-">Challenge</button>
      <button data-target="#{{$modalID ?? null}}-timer" type="button" class="btn btn-outline-secondary btn-">Time limit</button>
      <button data-target="#{{$modalID ?? null}}-practice" type="button" class="btn btn-outline-secondary btn-">Practice</button>
    </div>
  </div>

  <div id="{{$modalID ?? null}}-rounds">
    @include('games.challenges.components.settings.rounds')
  </div>

  <div id="{{$modalID ?? null}}-timer" style="display: none;">
    @include('games.challenges.components.settings.timer')
  </div>

  <div id="{{$modalID ?? null}}-practice" style="display: none;">
    @include('games.challenges.components.settings.practice')
  </div>
</div>
