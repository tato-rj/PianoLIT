@php($menuAccount = auth('web')->user())
<li class="nav-item d-flex align-items-center">
    @include('components.account.account-circle', ['account' => $menuAccount])
</li>
@once
    @push('scripts')
        @include('components.account.menu', ['account' => $menuAccount])
    @endpush
@endonce
