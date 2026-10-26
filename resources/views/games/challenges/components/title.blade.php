<div id="pagetitle" class="d-center text-grey pt-4 pb-2" data-game-settings="{{$settings->gameSlug()}}" data-game-options="{{json_encode($settings->replayOptions())}}">
  @include('games.challenges.components.identity')
</div>

@include('games.challenges.components.duel-progress')
