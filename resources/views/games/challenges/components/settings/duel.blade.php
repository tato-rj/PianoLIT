@if(array_key_exists($settings->gameSlug(), \App\Games\Duels\GameRegistry::GAMES))
<button type="button" data-duel-create="{{ $settings->gameSlug() }}" class="btn btn-white w-100 mt-3">@include('games.challenges.components.icon', ['icon' => 'bolt'])Start Multiplayer Duel</button>
@endif
