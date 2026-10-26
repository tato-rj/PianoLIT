@php($isNewPlayer = $player->is(session('newPlayer')))
<li class="leaderboard-player leaderboard-row {{$isNewPlayer ? 'is-you' : ''}}">
    <span class="leaderboard-row__rank" aria-label="Rank {{$rank}}">{{$rank}}</span>
    <img class="leaderboard-row__avatar" src="{{$player->avatar_url}}" alt="" width="36" height="36" loading="lazy">
    <span class="leaderboard-row__identity">
        <strong class="leaderboard-row__name" title="{{$player->username}}">{{$player->username}}</strong>
        @if($isNewPlayer)<span class="leaderboard-you">You</span>@endif
    </span>
    <span class="leaderboard-row__score">{{number_format($player->finalScore)}} <small>pts</small></span>
</li>
