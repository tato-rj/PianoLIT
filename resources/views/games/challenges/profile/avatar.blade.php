<div class="dropdown position-absolute top-0 end-0">
	<button id="user-avatar" type="button" aria-label="Your avatar" data-avatar-base="{{asset('images/games/avatars')}}/" style="padding: 0;" data-bs-toggle="dropdown" data-bs-offset="0,12" aria-expanded="false" aria-controls="user-avatar-menu">
		<span data-avatar-placeholder>@include('games.challenges.components.icon', ['icon' => 'circle-user', 'mr' => 0])</span>
		<img data-saved-avatar hidden alt="" width="52" height="52" class="w-100 h-100 rounded-circle">
	</button>
    <div id="user-avatar-menu" class="dropdown-menu dropdown-menu-end bg-white border border-dark rounded shadow-sm p-4" data-profile-menu aria-labelledby="user-avatar" style="width: 320px; max-width: calc(100vw - 32px);">
        <p class="small text-muted mb-0" data-profile-intro>Post a score on a leaderboard to choose an avatar and start tracking your scores.</p>
        <div data-profile-details hidden>
            <div class="mb-3">
                <div class="fw-bold fs-5 text-truncate" data-profile-name></div>
                <div class="small text-muted mt-1">Best scores</div>
            </div>
            <div class="dropdown-divider mt-0 mb-2"></div>
            <div style="max-height: 18rem; overflow-y: auto;">
                @foreach($games as $game)
                    @if($game->public())
                    <div class="d-flex align-items-center justify-content-between gap-3 py-2" data-profile-game="{{$game->gameName()}}" hidden>
                        <div class="d-flex align-items-center gap-2">
                            <span class="bg-{{$game->gameTheme()}} rounded-circle d-center flex-shrink-0" style="width: 32px; height: 32px;" aria-hidden="true">
                                @include('games.challenges.components.icon', ['icon' => $game->gameIcon(), 'mr' => 0])
                            </span>
                            <span class="small">{{$game->gameName()}}</span>
                        </div>
                        <strong class="text-nowrap" style="font-variant-numeric: tabular-nums;"><span data-profile-score></span> <small class="fw-normal text-muted">pts</small></strong>
                    </div>
                    @endif
                @endforeach
                <div class="small text-muted pt-2" data-profile-empty>Post a score to see it here.</div>
            </div>
        </div>
        <div class="text-end mt-3" data-profile-reset hidden>
            <button type="button" class="btn btn-raw btn-sm text-muted pb-0" data-profile-delete>@include('games.challenges.components.icon', ['icon' => 'trash-alt', 'fa_size' => 'sm', 'classes' => 'opacity-4'])Clear records</button>
        </div>
    </div>
</div>
