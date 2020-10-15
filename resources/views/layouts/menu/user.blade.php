<li class="nav-item dropdown mx-2">
  <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    {{auth()->user()->first_name}}
  </a>

  <div class="dropdown-menu p-2" style="font-size: .9em; left: initial; right: 0; min-width: auto" aria-labelledby="navbarDropdown">
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