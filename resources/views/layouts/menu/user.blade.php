<li class="nav-item dropdown mx-2">
  <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    {{auth()->user()->first_name}}
    @fa(['icon' => 'chevron-down', 'mr' => 0, 'styles' => 'font-size: 72%'])
  </a>

  <div class="dropdown-menu py-2 px-3 rounded animated fadeInUp" style="left: initial; right: 1px; min-width: auto" aria-labelledby="navbarDropdown">
{{--     <a class="nav-link" style="white-space: nowrap;" href="{{route('users.invite')}}">Invite Friends</a>
  
    <div class="dropdown-divider my-1"></div> --}}
  
    <a class="nav-link" style="white-space: nowrap;" href="{{route('users.profile')}}">My profile</a>
  
    <div class="dropdown-divider my-1"></div>
    
    <a class="nav-link" style="white-space: nowrap;" href="{{route('users.purchases')}}">My downloads</a>
  
    <div class="dropdown-divider my-1"></div>

    <a class="nav-link" style="white-space: nowrap;" href="{{route('contact')}}">Help & Support</a>
  
    <div class="dropdown-divider my-1"></div>
  
    <a class="nav-link" href="" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
      <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
        @csrf
      </form>Log out
    </a>
  </div>
</li>