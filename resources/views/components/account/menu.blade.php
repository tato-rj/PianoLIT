@php
    $account = $account ?? null;
    $preview = $designSystemPreview ?? false;
    $name = $account ? trim(($account->first_name ?? '').' '.($account->last_name ?? '')) : '';
    $panelId = $preview ? 'ds-account-navigation' : 'public-hub-menu';
    $shortcuts = $preview
        ? ['membership' => true, 'membershipUrl' => '#ds-components', 'downloads' => true, 'orders' => true]
        : app(\App\Services\Navigation\AccountMenu::class)->shortcuts($account);
    $currentAccountItem = null;
    if (! $preview) {
        foreach ([
            'profile' => ['users.profile', 'webapp.users.profile'],
            'membership' => ['webapp.membership.*'],
            'downloads' => ['users.purchases'],
            'orders' => ['users.orders'],
            'support' => ['contact'],
        ] as $item => $routes) {
            if (request()->routeIs(...$routes)) {
                $currentAccountItem = $item;
                break;
            }
        }
    }
@endphp
<div class="{{ $preview ? '' : 'offcanvas offcanvas-end ' }}account-menu account-navigation" id="{{ $panelId }}" aria-labelledby="{{ $panelId }}-title" @if($preview) data-ds-preview @else tabindex="-1" @endif>
    <div class="offcanvas-header account-navigation__header">
        <div class="account-menu__identity">
            @if($account)
                @include('components.account.account-circle', ['account' => $account, 'menuAvatar' => true])
            @endif
            <div class="account-menu__details">
                <h2 class="account-menu__name mb-0" id="{{ $panelId }}-title">{{ $account ? ($name ?: 'Your account') : 'Explore PianoLIT' }}</h2>
                @if($account)<div class="account-menu__email">{{ $account->email }}</div>@endif
            </div>
        </div>
        <button type="button" class="btn-close flex-shrink-0" @unless($preview) data-bs-dismiss="offcanvas" @endunless aria-label="Close account menu"></button>
    </div>

    <div class="offcanvas-body account-navigation__body">
        <nav class="account-menu__links" aria-label="Your account">
            @if($account)
                <a class="account-menu__link" href="{{ $preview ? '#ds-components' : route('users.profile') }}" @if($currentAccountItem === 'profile') aria-current="page" @endif>
                    @icon('user-round', ['mr' => 0])<span>My profile</span>
                </a>
            @else
                <a class="account-menu__link" href="{{ $preview ? '#ds-components' : route('login') }}">
                    @icon('user-round', ['mr' => 0])<span>Sign in</span>
                </a>
            @endif
            @if($shortcuts['membership'])
                <a class="account-menu__link" href="{{ $shortcuts['membershipUrl'] }}" @if($currentAccountItem === 'membership') aria-current="page" @endif>
                    @icon('credit-card', ['mr' => 0])<span>My membership</span>
                </a>
            @endif
            @if($shortcuts['downloads'])
                <a class="account-menu__link" href="{{ $preview ? '#ds-components' : route('users.purchases') }}" @if($currentAccountItem === 'downloads') aria-current="page" @endif>
                    @icon('download', ['mr' => 0])<span>My downloads</span>
                </a>
            @endif
            @if($shortcuts['orders'])
                <a class="account-menu__link" href="{{ $preview ? '#ds-components' : route('users.orders') }}" @if($currentAccountItem === 'orders') aria-current="page" @endif>
                    @icon('package', ['mr' => 0])<span>Track your orders</span>
                </a>
            @endif
        </nav>

        @include('components.navigation.hub-menu.links', ['designSystemPreview' => $preview, 'accountItemIsCurrent' => $currentAccountItem !== null])
    </div>

    <div class="account-menu__footer">
        <a class="account-menu__link" href="{{ $preview ? '#ds-components' : route('contact') }}" @if($currentAccountItem === 'support') aria-current="page" @endif>
            @icon('circle-help', ['mr' => 0])<span>Help &amp; Support</span>
        </a>
        @if($account)
            @if($preview)
                <button class="account-menu__link w-100 text-start" type="button">@icon('log-out', ['mr' => 0])<span>Log out</span></button>
            @else
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="account-menu__link w-100 text-start" type="submit">@icon('log-out', ['mr' => 0])<span>Log out</span></button>
                </form>
            @endif
        @endif
    </div>
</div>
@unless($preview)
    @include('components.account.scripts')
@endunless
