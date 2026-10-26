@component('games.challenges.components.modal', ['title' => \App\Games\GameIcon::render('ranking-star').'Leaderboard', 'class' => 'leaderboard', 'id' => 'leaderboard-'.$settings->gameSlug().'-modal', 'autoshow' => (bool) session('newPlayer')])

<div class="leaderboard-wrapper">
	<div class="text-center mb-3">
		<h6 class="leaderboard-game">{{$settings->gameName()}}</h6>
		@include('games.challenges.components.leaderboard.nav', ['name' => $settings->gameName()])
	</div>

	<div class="leaderboard-players">
		@include('games.challenges.components.leaderboard.list', ['leaderboard' => $settings->leaderboard(20)])
	</div>
</div>
@endcomponent
