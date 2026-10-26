@php($showHubMenu = ! request()->routeIs('home') && request()->getHost() !== 'my.'.config('app.short_url'))
<header class="site-header container pt-4 pt-md-5">
    <div class="row align-items-center justify-content-between">
        <div class="col-auto">
            <a href="{{ route('home') }}" class="d-inline-flex" aria-label="PianoLIT home">
                @brandIcon(['size' => 'clamp(48px, 7vw, 64px)'])
            </a>
        </div>
        <nav class="col-auto" aria-label="{{ request()->routeIs('home') ? 'Account and YouTube' : ($showHubMenu ? 'Account and site navigation' : 'Account') }}">
            <ul class="d-flex align-items-center gap-3 list-unstyled mb-0">
                @if(request()->routeIs('home'))
                    <li class="nav-item d-flex align-items-center">
                        <a href="https://www.youtube.com/pianolit" class="btn btn-secondary btn-sm d-inline-flex align-items-center" target="_blank" rel="noopener noreferrer">@fa(['icon' => 'youtube', 'fa_type' => 'b', 'color' => 'red'])YouTube</a>
                    </li>
                @endif
                @if(auth('web')->check() || request()->routeIs('games.home'))
                    @include('layouts.menu.user', ['accountCircle' => true, 'showGamesProfile' => request()->routeIs('games.home')])
                @else
                    <li class="nav-item d-flex align-items-center">
                        @include('components.account-circle')
                    </li>
                @endif
                @if($showHubMenu)
                    <li class="nav-item d-flex align-items-center">
                        @include('components.public-hub-menu-button')
                    </li>
                @endif
            </ul>
        </nav>
    </div>
</header>
@if($showHubMenu)
    @once
        @push('scripts')
            @include('components.public-hub-menu')
        @endpush
    @endonce
@endif
