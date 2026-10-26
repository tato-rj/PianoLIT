<li class="nav-item dropdown d-flex align-items-center">
    <button type="button" class="account-circle games-player-circle btn-raw d-inline-flex align-items-center justify-content-center rounded-circle"
            id="games-player-menu-button" aria-label="Game player menu" title="Game player"
            data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        @icon('trophy', ['mr' => 0])
    </button>
    <div class="dropdown-menu dropdown-menu-end games-player-menu py-2 px-3" aria-labelledby="games-player-menu-button">
        @include('games.challenges.profile.menu')
    </div>
</li>
