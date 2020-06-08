<nav class="navbar navbar-expand-lg navbar-light py-5">
  <a class="navbar-brand" href="{{config('app.url')}}">
      <img src="{{asset('images/brand/app-icon.svg')}}" style="border-radius: 20%; width: 60px">
  </a>
  <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>

  <div class="collapse navbar-collapse" id="navbarSupportedContent">
    <ul class="navbar-nav ml-auto">
      <li class="nav-item dropdown mx-2">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          Tools
        </a>
        <div class="dropdown-menu p-2" style="font-size: .9em" aria-labelledby="navbarDropdown">
          <label style="font-size: .9em" class="m-0 text-grey"><small>THEORY</small></label>
          <a class="nav-link p-0 mb-1 ml-1" href="{{route('tools.circle-of-fifths')}}">Circle of Fifths</a>
          <a class="nav-link p-0 mb-1 ml-1" href="{{route('tools.chord-finder.index')}}">Chord Finder</a>
          <div class="dropdown-divider"></div>
          <label style="font-size: .9em" class="m-0 text-grey"><small>TECHNIQUE</small></label>
          <a class="nav-link p-0 mb-1 ml-1" href="{{route('tools.scales.index')}}">Scales</a>
          <a class="nav-link p-0 mb-1 ml-1" href="{{route('tools.arpeggios.index')}}">Arpeggios</a>
          <div class="dropdown-divider"></div>
          <label style="font-size: .9em" class="m-0 text-grey"><small>FOR TEACHERS</small></label>
          <a class="nav-link p-0 mb-1 ml-1" href="{{route('tools.studio-policies')}}">Studio Policy Generator</a>
          <a class="nav-link p-0 ml-1" href="{{route('tools.staff')}}">Staff Generator</a>
        </div>
      </li>
      <li class="nav-item dropdown mx-2">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          Resources
        </a>
        <div class="dropdown-menu p-2" style="font-size: .9em" aria-labelledby="navbarDropdown">
          <label style="font-size: .9em" class="m-0 text-grey"><small>TO LEARN</small></label>
          <a class="nav-link p-0 ml-1" href="{{route('resources.infographs.index')}}">Infographics</a>
          <a class="nav-link p-0 ml-1" href="{{route('resources.timeline')}}">Music Timeline</a>
          <div class="dropdown-divider"></div>
          <label style="font-size: .9em" class="m-0 text-grey"><small>TO HEAR</small></label>
          <a class="nav-link p-0 ml-1" href="{{route('resources.pianists.index')}}">Great Pianists</a>
          <label style="font-size: .9em" class="m-0 text-grey"><small>TO PLAY</small></label>
          <a class="nav-link p-0 mb-2 ml-1" href="{{route('quizzes.index')}}">Quizzes</a>
          <a class="nav-link p-0 mb-2 ml-1" href="{{route('true-or-false.index')}}">True or False</a>
          <a class="nav-link p-0 ml-1" href="{{route('riddles')}}">Riddles</a>
        </div>
      </li>
      <li class="nav-item mx-2">
        <a class="nav-link" href="{{route('posts.index')}}">Blog</a>
      </li>
      <li class="nav-item mx-2">
        <a class="nav-link" href="{{route('webapp.discover')}}">WebApp</a>
      </li>
      @auth
      <li class="nav-item dropdown mx-2">
        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
          {{auth()->user()->first_name}}
        </a>
        <div class="dropdown-menu p-2" style="font-size: .9em; left: initial; right: 0; min-width: auto" aria-labelledby="navbarDropdown">
{{--           <a class="nav-link" style="white-space: nowrap;" href="{{route('users.invite')}}">Invite Friends</a>
          <div class="dropdown-divider my-1"></div> --}}
          <a class="nav-link" style="white-space: nowrap;" href="{{route('users.profile')}}">My profile</a>
          <div class="dropdown-divider my-1"></div>
          @if(auth()->user()->studioPolicies()->exists())
          <a class="nav-link" style="white-space: nowrap;" href="{{route('users.studio-policies.index')}}">My Studio Policies</a>
          <div class="dropdown-divider my-1"></div>
          @endif
          <a class="nav-link" style="white-space: nowrap;" href="{{route('contact')}}">Help & Support</a>
          <div class="dropdown-divider my-1"></div>
          <a class="nav-link" href="" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">@csrf</form>Log out
          </a>
        </div>
      </li>
      @else
      <li class="nav-item mx-2">
        <a class="nav-link" href="{{route('login')}}">Log in</a>
      </li>
      @endauth
      <li class="nav-item mx-2">
        <button class="nav-link bg-transparent border-0 show-overlay" data-target="#global-search-overlay"><i class="fas fa-search"></i><span class="ml-2 d-inline-block d-sm-none">Search here</span></button>
      </li>
    </ul>
  </div>
</nav>