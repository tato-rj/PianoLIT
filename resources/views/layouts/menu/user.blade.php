@php($menuAccount = auth('web')->user())
@if($webappNavigation ?? false)
<div class="text-center position-relative" id="menu-account">
    @include('components.account.account-circle', ['account' => $menuAccount, 'variant' => 'webapp-navigation'])
</div>
@else
<li class="nav-item d-flex align-items-center">
    @include('components.account.account-circle', ['account' => $menuAccount])
</li>
@endif
@once
    @push('scripts')
        @include('components.account.menu', ['account' => $menuAccount])
    @endpush
@endonce
