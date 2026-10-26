<div id="pagetitle" class="d-center text-grey pt-4 pb-2" data-game-settings="{{$settings->gameSlug()}}" data-game-options="{{json_encode($settings->replayOptions())}}">
  @brandIcon(['size' => '18px', 'classes' => 'd-block mr-2'])
  <label class="prevent-select small fw-bold text-uppercase">{{$settings->gameName()}}</label>
</div>

@include('games.challenges.components.duel-progress')
