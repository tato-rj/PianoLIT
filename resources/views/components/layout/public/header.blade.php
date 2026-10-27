@php($showHubMenu = ! request()->routeIs('home') && request()->getHost() !== 'my.'.config('app.short_url'))
<header class="site-header container pt-4 pt-md-5">
    <div class="row align-items-center justify-content-between mx-lg-4">
        <div class="col-auto">
            <a href="{{ route('home') }}" class="d-inline-flex align-items-center gap-2 text-body text-decoration-none" aria-label="PianoLIT home">
                @brandIcon(['size' => 'clamp(48px, 7vw, 64px)', 'tilt' => ! request()->routeIs('home')])
                @if($showBrandName ?? false)<span class="site-header__name">PianoLIT</span>@endif
            </a>
        </div>
        <nav class="col-auto ms-auto" aria-label="{{ request()->routeIs('home') ? 'Account and YouTube' : ($showHubMenu ? 'Account and site navigation' : 'Account') }}">
            <ul class="d-flex align-items-center gap-3 list-unstyled mb-0">
                @if(request()->routeIs('home'))
                    <li class="nav-item d-flex align-items-center">
                        @include('components.navigation.youtube-button')
                    </li>
                @endif
                @if(auth('web')->check())
                    @include('layouts.menu.user', ['accountCircle' => true])
                    @if(request()->routeIs('games.home'))
                        @include('games.challenges.profile.dropdown')
                    @endif
                @else
                    <li class="nav-item d-flex align-items-center">
                        @include('components.account.account-circle')
                    </li>
                @endif
                @if($showHubMenu)
                    <li class="nav-item d-flex align-items-center">
                        @include('components.navigation.hub-menu.button')
                    </li>
                @endif
            </ul>
        </nav>
    </div>
</header>
@if($showHubMenu)
    @once
        @push('scripts')
            @include('components.navigation.hub-menu.panel')
        @endpush
    @endonce
@endif
