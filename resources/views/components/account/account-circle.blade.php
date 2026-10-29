@php
    $account = $account ?? null;
    $accountName = trim((string) ($account->first_name ?? ''));
    $accountInitial = $accountName === '' ? 'U' : mb_strtoupper(mb_substr($accountName, 0, 1));
    $accountImage = app(\App\Services\Navigation\AccountAvatar::class)->forAccount($account);
    $accountClasses = 'account-circle btn-raw d-inline-flex align-items-center justify-content-center rounded-circle text-decoration-none';
@endphp
@if($menuAvatar ?? false)
    <span class="{{ $accountClasses }} account-menu__avatar" aria-hidden="true">
        <span class="account-circle__initial">{{ $accountInitial }}</span>
        @if($accountImage)<img class="account-circle__image" src="{{ $accountImage }}" alt="" referrerpolicy="no-referrer" data-account-avatar>@endif
    </span>
@else
    <button type="button" class="{{ $accountClasses }}" @if(! ($designSystemPreview ?? false) || isset($menuId)) id="{{ $menuId ?? 'navbarDropdown' }}" @endif
            aria-label="{{ $accountName === '' ? 'Account menu' : 'Account menu for '.$accountName }}"
            title="{{ $accountName === '' ? 'Account' : $accountName }}"
            @if($designSystemPreview ?? false) data-ds-preview @else data-bs-toggle="offcanvas" data-bs-target="#public-hub-menu" data-account-navigation-toggle aria-controls="public-hub-menu" aria-haspopup="dialog" aria-expanded="false" @endif>
        @if($account)
            <span class="account-circle__initial" aria-hidden="true">{{ $accountInitial }}</span>
            @if($accountImage)<img class="account-circle__image" src="{{ $accountImage }}" alt="" referrerpolicy="no-referrer" data-account-avatar>@endif
        @else
            @icon('user-round', ['mr' => 0])
        @endif
    </button>
@endif
