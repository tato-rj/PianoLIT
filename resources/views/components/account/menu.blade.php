@php
    $account = $account ?? null;
    $preview = $designSystemPreview ?? false;
    $name = trim(($account->first_name ?? '').' '.($account->last_name ?? ''));
@endphp
@component('components.account.menu-panel', ['menuId' => $menuId ?? 'navbarDropdown', 'designSystemPreview' => $preview])
    @if($account)
        <div class="account-menu__identity">
            <div class="account-menu__details">
                <div class="account-menu__name">{{ $name ?: 'Your account' }}</div>
                <div class="account-menu__email">{{ $account->email }}</div>
            </div>
            @if($preview)
                @include('components.account.account-circle', ['account' => $account, 'menuAvatar' => true])
            @endif
        </div>
    @endif

    <div class="account-menu__links">
        @if($account)
            <a class="dropdown-item account-menu__link" href="{{ $preview ? '#ds-components' : route('users.profile') }}" @if(! $preview && request()->routeIs('users.profile', 'webapp.users.profile')) aria-current="page" @endif>
                @icon('user-round', ['mr' => 0])<span>My profile</span>
            </a>
            <a class="dropdown-item account-menu__link" href="{{ $preview ? '#ds-components' : route('users.purchases') }}" @if(! $preview && request()->routeIs('users.purchases')) aria-current="page" @endif>
                @icon('download', ['mr' => 0])<span>My downloads</span>
            </a>
        @else
            <a class="dropdown-item account-menu__link" href="{{ $preview ? '#ds-components' : route('login') }}">
                @icon('user-round', ['mr' => 0])<span>Sign in</span>
            </a>
        @endif
        <a class="dropdown-item account-menu__link" href="{{ $preview ? '#ds-components' : route('contact') }}" @if(! $preview && request()->routeIs('contact')) aria-current="page" @endif>
            @icon('circle-help', ['mr' => 0])<span>Help &amp; Support</span>
        </a>
    </div>

    @if($account)
        <div class="account-menu__footer">
            @if($preview)
                <button class="dropdown-item account-menu__link" type="button">@icon('log-out', ['mr' => 0])<span>Log out</span></button>
            @else
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="dropdown-item account-menu__link" type="submit">@icon('log-out', ['mr' => 0])<span>Log out</span></button>
                </form>
            @endif
        </div>
    @endif
@endcomponent
