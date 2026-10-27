<section data-games-profile data-preserve-icons data-avatar-base="{{ asset('images/games/avatars') }}/" aria-labelledby="{{ $profileTitleId ?? 'games-profile-title' }}">
    <div class="account-menu__identity">
        <div class="account-menu__details">
            <h2 id="{{ $profileTitleId ?? 'games-profile-title' }}" class="account-menu__name mb-0">Game player</h2>
            <div class="account-menu__email">Best scores on this browser</div>
        </div>
        @if($designSystemPreview ?? false)
            @include('games.challenges.profile.button', ['designSystemPreview' => true])
        @endif
    </div>
    <div class="account-menu__links">
        <p class="small text-muted px-3 py-2 mb-0" data-profile-intro>Post a score on a leaderboard to choose an avatar and start tracking your scores.</p>
        <div data-profile-details hidden>
            <div class="d-flex align-items-center gap-3 px-3 py-2 mb-2">
                <img data-saved-avatar hidden alt="" width="40" height="40" class="rounded-circle flex-shrink-0">
                <div class="overflow-hidden">
                    <div class="fw-bold text-truncate" data-profile-name></div>
                </div>
            </div>
            <div class="games-profile-records">
                @foreach($games as $game)
                    @if($game->public())
                        <div class="account-menu__row" data-profile-game="{{ $game->gameName() }}" hidden>
                            <span class="account-menu__icon text-muted" aria-hidden="true">@include('games.challenges.components.icon', ['icon' => $game->gameIcon(), 'mr' => 0])</span>
                            <span class="flex-grow-1">{{ $game->gameName() }}</span>
                            <strong class="text-nowrap"><span data-profile-score></span> <small class="fw-normal text-muted">pts</small></strong>
                        </div>
                    @endif
                @endforeach
                <div class="small text-muted px-3 py-2" data-profile-empty>Post a score to see it here.</div>
            </div>
        </div>
    </div>
    <div class="account-menu__footer" data-profile-reset hidden>
        <button type="button" class="dropdown-item account-menu__link" data-profile-delete>@icon('trash-2', ['mr' => 0])<span>Clear records</span></button>
    </div>
</section>
