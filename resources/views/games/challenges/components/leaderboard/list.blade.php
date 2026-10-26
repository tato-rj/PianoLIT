@if($leaderboard->isEmpty())
    <div class="leaderboard-empty">
        <span class="leaderboard-empty__icon" aria-hidden="true">@include('games.challenges.components.icon', ['icon' => 'ranking-star', 'mr' => 0])</span>
        <p>No scores yet. Be the first!</p>
    </div>
@else
    <ol class="leaderboard-podium" aria-label="Top three">
        @foreach($leaderboard->take(3) as $player)
            @php($rank = $loop->iteration)
            @php($isNewPlayer = $player->is(session('newPlayer')))
            <li class="leaderboard-podium__player leaderboard-podium__player--{{$rank}} {{$isNewPlayer ? 'is-you' : ''}}" aria-label="Rank {{$rank}}: {{$player->username}}, {{$player->finalScore}} points">
                <div class="leaderboard-podium__portrait">
                    @if($rank === 1)
                        <span class="leaderboard-podium__crown" aria-hidden="true">@include('games.challenges.components.icon', ['icon' => 'crown', 'mr' => 0])</span>
                    @endif
                    <img src="{{$player->avatar_url}}" alt="" width="88" height="88">
                    <span class="leaderboard-podium__rank" aria-hidden="true">{{$rank}}</span>
                </div>
                <strong class="leaderboard-podium__name" title="{{$player->username}}">{{$player->username}}</strong>
                <span class="leaderboard-podium__score">{{number_format($player->finalScore)}} <small>pts</small></span>
                @if($isNewPlayer)<span class="leaderboard-you">You</span>@endif
            </li>
        @endforeach
    </ol>
    @if($leaderboard->count() > 3)
        <ol class="leaderboard-ranks" start="4" aria-label="More scores">
            @foreach($leaderboard->skip(3) as $player)
                @include('games.challenges.components.leaderboard.player', ['rank' => $loop->iteration + 3])
            @endforeach
        </ol>
    @endif
@endif
