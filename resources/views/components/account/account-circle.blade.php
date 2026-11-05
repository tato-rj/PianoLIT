@php
    $account = $account ?? null;
    $closeControl = $closeControl ?? false;
    $accountName = trim((string) ($account->first_name ?? ''));
    $accountInitial = $accountName === '' ? 'U' : mb_strtoupper(mb_substr($accountName, 0, 1));
    $accountImage = $closeControl ? null : app(\App\Services\Navigation\AccountAvatar::class)->forAccount($account);
    $navigationVariant = ! $closeControl && ($variant ?? null) === 'webapp-navigation' && ! ($menuAvatar ?? false);
    $accountLabel = $account ? ($accountName ?: 'Account') : 'Log in';
    $accountClasses = 'account-circle btn-raw d-inline-flex align-items-center justify-content-center text-decoration-none '.($navigationVariant ? 'account-circle--navigation' : 'rounded-circle').($closeControl ? ' account-circle--close' : '');
@endphp
@if($menuAvatar ?? false)
    <span class="{{ $accountClasses }} account-menu__avatar" aria-hidden="true">
        <span class="account-circle__initial">{{ $accountInitial }}</span>
        @if($accountImage)<img class="account-circle__image" src="{{ $accountImage }}" alt="" referrerpolicy="no-referrer" data-account-avatar>@endif
    </span>
@else
    <button type="button" class="{{ $navigationVariant ? '' : 'shadow ' }}{{ $accountClasses }}" @if(! ($designSystemPreview ?? false) || isset($menuId)) id="{{ $menuId ?? ($closeControl ? 'account-navigation-close' : 'navbarDropdown') }}" @endif
            aria-label="{{ $closeControl ? 'Close account menu' : ($navigationVariant && ! $account ? 'Log in' : ($accountName === '' ? 'Account menu' : 'Account menu for '.$accountName)) }}"
            title="{{ $closeControl ? 'Close account menu' : ($navigationVariant ? $accountLabel : ($accountName === '' ? 'Account' : $accountName)) }}"
            @if($designSystemPreview ?? false) data-ds-preview @elseif($closeControl) data-bs-dismiss="offcanvas" data-bs-target="#{{ $panelId ?? 'public-hub-menu' }}" @else data-bs-toggle="offcanvas" data-bs-target="#public-hub-menu" data-account-navigation-toggle aria-controls="public-hub-menu" aria-haspopup="dialog" aria-expanded="false" @endif>
        @unless($closeControl)
        @if($navigationVariant)<span class="account-circle__visual webapp-menu-icon-row {{ $account ? '' : 'account-circle__visual--guest' }}" aria-hidden="true">@endif
        @if($account)
            <span class="account-circle__initial" aria-hidden="true">{{ $accountInitial }}</span>
            @if($accountImage)<img class="account-circle__image" src="{{ $accountImage }}" alt="" referrerpolicy="no-referrer" data-account-avatar>@endif
        @else
            @if($navigationVariant)
                @icon('log-in', ['mr' => 0])
            @else
                @icon('user-round', ['mr' => 0])
            @endif
        @endif
        @if($navigationVariant)</span>@endif
        @endunless
        @icon('x', ['mr' => 0, 'classes' => 'account-circle__close'])
        @if($navigationVariant)<small class="account-circle__label webapp-menu-label">{{ $accountLabel }}</small>@endif
    </button>
@endif
