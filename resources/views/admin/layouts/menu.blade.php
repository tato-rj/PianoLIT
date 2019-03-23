<nav class="navbar navbar-expand-lg navbar-light fixed-top" id="mainNav">
  <a class="navbar-brand mr-0" href="/piano-lit"><img src="{{asset('images/brand/app-icon.svg')}}" class="mr-2"><span class="text-brand">Piano<strong>LIT</strong></span></a>
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
      <li class="nav-item">
        <a class="nav-link" href="">
          <i class="fas fa-pencil-alt fa-fw"></i>
          <span class="nav-link-text">My profile</span>
        </a>
      </li>
      @endeditor

      <li class="nav-item">
        <a class="nav-link nav-link-collapse collapsed" data-toggle="collapse" href="#statistics">
          <i class="fas fa-chart-line fa-fw"></i>
          <span class="nav-link-text">Statistics</span>
        </a>
        <ul class="sidenav-second-level collapse" id="statistics">
          <li>
            <a class="py-2" href="{{route('admin.stats.users')}}">Users</a>
          </li>
          <li>
            <a class="py-2" href="{{route('admin.stats.pieces')}}">Pieces</a>
          </li>
          <li>
            <a class="py-2" href="{{route('admin.stats.blog')}}">Blog</a>
          </li>
        </ul>
      </li>      
     
      @manager
      <li class="nav-item">
        <a class="nav-link nav-link-collapse collapsed" data-toggle="collapse" href="#api">
          <i class="fas fa-code fa-fw"></i>
          <span class="nav-link-text">Api</span>
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
      <li class="nav-item">
        <a class="nav-link" href="{{route('admin.tags.index')}}">
          <i class="fas fa-fw fa-tags"></i>
          <span class="nav-link-text">Tags</span>
        </a>
      </li>
      @endmanager
      <li class="nav-item">
        <a class="nav-link" href="{{route('admin.pieces.index')}}">
          <i class="fa fa-fw fa-music"></i>
          <span class="nav-link-text">Pieces</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="{{route('admin.composers.index')}}">
          <i class="fas fa-fw fa-address-card"></i>
          <span class="nav-link-text">Composers</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="{{route('admin.posts.index')}}">
          <i class="fas fa-fw fa-newspaper"></i>
          <span class="nav-link-text">Blog</span>
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