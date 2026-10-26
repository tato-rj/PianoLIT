@if(array_key_exists($settings->gameSlug(), \App\Games\Duels\GameRegistry::GAMES))
@auth('web')
<button type="button" data-duel-create="{{ $settings->gameSlug() }}" class="btn btn-white w-100 mt-3">@include('games.challenges.components.icon', ['icon' => 'bolt'])Start Multiplayer Duel</button>
@else
<a href="{{ route('login') }}" class="btn btn-white w-100 mt-3">@include('games.challenges.components.icon', ['icon' => 'bolt'])Sign in to start a duel</a>
@endauth
@endif
