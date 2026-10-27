<li class="nav-item dropdown d-flex align-items-center">
    @include('games.challenges.profile.button')
    @component('components.account.menu-panel', ['menuId' => 'games-player-menu-button', 'menuClasses' => 'games-player-menu'])
        @include('games.challenges.profile.menu')
    @endcomponent
</li>
