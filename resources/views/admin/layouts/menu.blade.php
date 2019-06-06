<nav class="navbar navbar-expand-lg navbar-light fixed-top" id="mainNav">
  <a class="navbar-brand mr-0" href="{{route('admin.home')}}"><img src="{{asset('images/brand/admin-icon.svg')}}" class="mr-2 shadow-sm">Piano<strong>LIT</strong> | Admin</a>
  <button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
  <div class="collapse navbar-collapse" id="navbarResponsive">
    <ul class="navbar-nav navbar-sidenav">
      <li class="nav-item">
        <a class="nav-link" href="{{route('admin.home')}}">
          <i class="fas fa-lightbulb fa-fw fa-tachometer-alt"></i>
          <span class="nav-link-text">Dashboard</span>
        </a>
      </li>
      @editor
      <li class="nav-item d-none d-sm-block">
        <a class="nav-link" href="">
          <i class="fas fa-pencil-alt fa-fw"></i>
          <span class="nav-link-text">My profile</span>
        </a>
      </li>
      @endeditor

      <li class="nav-item d-none d-sm-block">
        <a class="nav-link nav-link-collapse collapsed" data-toggle="collapse" href="#statistics">
          <div>
            <i class="fas fa-chart-line fa-fw"></i>
            <span class="nav-link-text">Statistics</span>
          </div>
          <div>
            <i class="fas fa-caret-down"></i>
          </div>
        </a>
        <ul class="sidenav-second-level collapse" id="statistics">
          <li>
            <a class="py-2" href="{{route('admin.stats.users')}}">Users</a>
          </li>
          <li>
            <a class="py-2" href="{{route('admin.stats.pieces')}}">Pieces</a>
          </li>
          <li>
            <a class="py-2" href="{{route('admin.stats.composers')}}">Composers</a>
          </li>
          <li>
            <a class="py-2" href="{{route('admin.stats.blog')}}">Blog</a>
          </li>
        </ul>
      </li>      

      @manager
      <li class="nav-item d-none d-sm-block">
        <a class="nav-link nav-link-collapse collapsed" data-toggle="collapse" href="#api">
          <div>
            <i class="fas fa-code fa-fw"></i>
            <span class="nav-link-text">Api</span>
          </div>
          <div>
            <i class="fas fa-caret-down"></i>
          </div>
        </a>
        <ul class="sidenav-second-level collapse" id="api">
          <li>
            <a class="py-2" href="{{route('admin.api.discover')}}">Discover</a>
          </li>
          <li>
            <a class="py-2" href="{{route('admin.api.search')}}">Search</a>
          </li>
          <li>
            <a class="py-2" href="{{route('admin.api.tour')}}">Tour</a>
          </li>
        </ul>
      </li>

      <li class="nav-item">
        <a class="nav-link nav-link-collapse collapsed" data-toggle="collapse" href="#menu-repertoire">
          <div>
            <i class="fas fa-music fa-fw"></i>
            <span class="nav-link-text">Repertoire</span>
          </div>
          <div>
            <i class="fas fa-caret-down"></i>
          </div>
        </a>
        <ul class="sidenav-second-level collapse" id="menu-repertoire">
          <li>
            <a class="py-2" href="{{route('admin.pieces.index')}}">Pieces</a>
          </li>
          <li>
            <a class="py-2" href="{{route('admin.composers.index')}}">Composers</a>
          </li>
          <li>
            <a class="py-2" href="{{route('admin.tags.index')}}">Tags</a>
          </li>
        </ul>
      </li>

      <li class="nav-item">
        <a class="nav-link nav-link-collapse collapsed" data-toggle="collapse" href="#blog">
          <div>
            <i class="fas fa-newspaper fa-fw"></i>
            <span class="nav-link-text">Blog</span>
          </div>
          <div>
            <i class="fas fa-caret-down"></i>
          </div>
        </a>
        <ul class="sidenav-second-level collapse" id="blog">
          <li>
            <a class="py-2" href="{{route('admin.posts.index')}}">Posts</a>
          </li>
          <li>
            <a class="py-2" href="{{route('admin.topics.index')}}">Topics</a>
          </li>
          <li class="d-none d-sm-block">
            <a class="py-2" href="{{route('admin.posts.audio.index')}}">Audio</a>
          </li>
        </ul>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="{{route('admin.timelines.index')}}">
          <i class="fas fa-list-ul"></i>
          <span class="nav-link-text">Timeline</span>
        </a>
      </li>
      
      <li class="nav-item d-none d-sm-block">
        <a class="nav-link" href="{{route('admin.editors.index')}}">
          <i class="fas fa-pencil-alt fa-fw"></i>
          <span class="nav-link-text">Editors</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="{{route('admin.users.index')}}">
          <i class="fa fa-fw fa-users"></i>
          <span class="nav-link-text">Users</span>
        </a>
      </li>
      @endmanager
      <li class="nav-item">
        <a class="nav-link" href="{{route('admin.subscriptions.index')}}">
          <i class="fas fa-fw fa-at"></i>
          <span class="nav-link-text">Subscriptions</span>
        </a>
      </li>

    </ul>
    <ul class="navbar-nav sidenav-toggler">
      <li class="nav-item">
        <a class="nav-link text-center" id="sidenavToggler">
          <i class="fa fa-fw fa-angle-left"></i>
        </a>
      </li>
    </ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item">
        <a class="nav-link" data-toggle="modal" data-target="#logoutModal">
          <i class="fas fa-fw fa-sign-out-alt"></i>Logout</a>
        </li>
      </ul>
    </div>
  </nav>