@php($menuAccount = auth('web')->user())
<li class="nav-item dropdown{{ ($accountCircle ?? false) ? ' d-flex align-items-center' : ' mx-2' }}">
  @if($accountCircle ?? false)
    @include('components.account.account-circle', ['account' => $menuAccount])
  @else
  <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    {{$menuAccount->first_name ?? 'Account'}}
    @icon('chevron-down', ['mr' => 0, 'styles' => 'font-size: 72%'])
  </a>
  @endif

  @include('components.account.menu', ['account' => $menuAccount])
</li>
