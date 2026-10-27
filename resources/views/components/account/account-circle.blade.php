@php
    $account = $account ?? null;
    $accountName = trim((string) ($account->first_name ?? ''));
    $accountInitial = $accountName === '' ? 'U' : mb_strtoupper(mb_substr($accountName, 0, 1));
    $accountClasses = 'account-circle btn-raw d-inline-flex align-items-center justify-content-center rounded-circle text-decoration-none';
@endphp
@if($menuAvatar ?? false)
    <span class="{{ $accountClasses }} account-menu__avatar" aria-hidden="true">
        <span class="account-circle__initial">{{ $accountInitial }}</span>
    </span>
@elseif($account)
    <button type="button" class="{{ $accountClasses }}" id="{{ $menuId ?? 'navbarDropdown' }}"
            aria-label="{{ $accountName === '' ? 'Account menu' : 'Account menu for '.$accountName }}"
            title="{{ $accountName === '' ? 'Account' : $accountName }}"
            @if($designSystemPreview ?? false) data-ds-preview @else data-bs-toggle="dropdown" data-bs-display="static" data-account-menu-toggle aria-haspopup="true" aria-expanded="false" @endif>
        @if($account)
            <span class="account-circle__initial" aria-hidden="true">{{ $accountInitial }}</span>
        @else
            @icon('user-round', ['mr' => 0])
        @endif
    </button>
@else
    <a href="{{ ($designSystemPreview ?? false) ? '#ds-buttons' : route('login') }}" class="{{ $accountClasses }}" aria-label="Sign in" title="Sign in" @if($designSystemPreview ?? false) data-ds-preview @endif>
        @icon('user-round', ['mr' => 0])
    </a>
@endif
