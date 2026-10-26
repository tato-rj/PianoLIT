@php($menuAccount = auth('web')->user())
<li class="nav-item dropdown{{ ($accountCircle ?? false) ? ' d-flex align-items-center' : ' mx-2' }}">
  @if($accountCircle ?? false)
    @include('components.account-circle', ['account' => $menuAccount, 'guestMenu' => true])
  @else
  <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    {{auth()->user()->first_name}}
    @icon('chevron-down', ['mr' => 0, 'styles' => 'font-size: 72%'])
  </a>
  @endif

  <div class="dropdown-menu py-2 px-3 {{ ($showGamesProfile ?? false) ? 'games-account-menu dropdown-menu-end' : 'animated fadeInUp' }}" style="left: initial; right: 1px; min-width: auto" aria-labelledby="navbarDropdown">
    @if($menuAccount)
        <a class="nav-link" style="white-space: nowrap;" href="{{route('users.profile')}}">My profile</a>
        <div class="dropdown-divider my-1"></div>
        <a class="nav-link" style="white-space: nowrap;" href="{{route('users.purchases')}}">My downloads</a>
    @else
        <a class="nav-link" style="white-space: nowrap;" href="{{route('login')}}" aria-label="Sign in">Sign in</a>
    @endif
    <div class="dropdown-divider my-1"></div>

    <a class="nav-link" style="white-space: nowrap;" href="{{route('contact')}}">Help & Support</a>

    @if($showGamesProfile ?? false)
        <div class="dropdown-divider my-2"></div>
        @include('games.challenges.profile.menu')
    @endif

    @if($menuAccount)
    <div class="dropdown-divider my-1"></div>

    <a class="nav-link" href="" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
      <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
      </form>Log out
    </a>
    @endif
  </div>
</li>
