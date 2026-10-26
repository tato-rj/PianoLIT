@php
    $seeds = range(1, 78);
    shuffle($seeds);
@endphp

@component('games.challenges.components.modal', ['title' => 'Join the Leaderboard', 'id' => 'save-results-modal', 'data' => ['final-points-url' => route('games.leaderboard.final-points')]])

<div class="mb-3">
    <div style="font-size: 65%" class="text-center lh-1 fw-bold text-muted">FINAL POINTS</div>
    <h1 id="finalPoints" class="text-center lh-1 text-primary" style="font-size: 4rem;"></h1>
    <p class="m-0 fst-italic text-muted small">@include('games.challenges.components.icon', ['icon' => 'lightbulb', 'fa_color' => 'yellow'])<strong>How is this calculated?</strong> Higher score, better accuracy, more rounds, faster time, and bonus settings will all increase your final points.</p>
</div>

<form method="POST" action="{{route('games.leaderboard.store')}}" data-leaderboard-profile
    @if(session('newPlayer'))
        data-posted-name="{{session('newPlayer')->username}}"
        data-posted-avatar="{{session('newPlayer')->avatar_url}}"
        data-posted-game="{{session('newPlayer')->game}}"
        data-posted-score="{{session('newPlayer')->finalScore}}"
    @endif
>
    @csrf

    <input type="hidden" name="game" value="{{$settings->gameName()}}">
    <input type="hidden" name="return_options" value="{{ json_encode($settings->replayOptions()) }}">

    <input type="hidden" name="rounds">
    <input type="hidden" name="score">
    <input type="hidden" name="accuracy">
    <input type="hidden" name="duration">

    <div class="form-group">
        <label class="text-grey mb-1 small">Select your username</label>
        <input class="form-control" required placeholder="Username" name="username">
    </div>
    <div class="form-group">
        <label class="text-grey mb-1 small">Select your avatar</label>

        <div class="btn-group align-items-center justify-content-center flex-wrap avatar-picker" style="height: 240px; overflow-y: scroll;" role="group">

            @foreach($seeds as $seed)
                <div class="m-2 cursor-pointer avatar-option">
                    <input
                        type="radio"
                        required
                        class="btn-check"
                        name="avatar_url"
                        id="avatar-{{ $seed }}"
                        value="{{asset('images/games/avatars/avatar-'.$seed.'.svg')}}"
                        autocomplete="off"
                    >
                    <img
                        class="player-avatar"
                        src="{{asset('images/games/avatars/avatar-'.$seed.'.svg')}}"
                        alt="Avatar {{ $seed }}"
                        style="width:52px; height:52px;"
                    >
                </div>
            @endforeach
        </div>
    </div>

    <button type="submit" class="btn btn-primary w-100">Post my results</button>
</form>

@endcomponent
