<section data-games-profile data-preserve-icons data-avatar-base="{{ asset('images/games/avatars') }}/" aria-labelledby="games-profile-title">
    <h2 id="games-profile-title" class="dropdown-header px-0 mb-2">Game player</h2>
    <p class="small text-muted mb-0" data-profile-intro>Post a score on a leaderboard to choose an avatar and start tracking your scores.</p>
    <div data-profile-details hidden>
        <div class="d-flex align-items-center gap-3 mb-3">
            <img data-saved-avatar hidden alt="" width="40" height="40" class="rounded-circle flex-shrink-0">
            <div class="overflow-hidden">
                <div class="fw-bold text-truncate" data-profile-name></div>
                <div class="small text-muted">Best scores</div>
            </div>
        </div>
        <div class="games-profile-records">
            @foreach($games as $game)
                @if($game->public())
                    <div class="d-flex align-items-center justify-content-between gap-3 py-2" data-profile-game="{{ $game->gameName() }}" hidden>
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted" aria-hidden="true">@include('games.challenges.components.icon', ['icon' => $game->gameIcon(), 'mr' => 0])</span>
                            <span class="small">{{ $game->gameName() }}</span>
                        </div>
                        <strong class="text-nowrap"><span data-profile-score></span> <small class="fw-normal text-muted">pts</small></strong>
                    </div>
                @endif
            @endforeach
            <div class="small text-muted pt-2" data-profile-empty>Post a score to see it here.</div>
        </div>
    </div>
    <div class="text-end mt-3" data-profile-reset hidden>
        <button type="button" class="btn btn-raw btn-sm text-muted pb-0" data-profile-delete>@icon('trash-2', ['size' => 'sm'])Clear records</button>
    </div>
</section>
