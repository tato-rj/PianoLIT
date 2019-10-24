<nav class="navbar navbar-expand-lg navbar-light fixed-top" id="mainNav">
  <a class="navbar-brand mr-0" href="{{route('admin.home')}}">
    <img src="{{asset('images/brand/admin-icon.svg')}}" class="mr-2 shadow-sm">Piano<strong>LIT</strong> | Admin
  </a>
  <div>
    <li class="nav-item inline-on-collapse text-muted">
      <a class="nav-link position-relative cursor-pointer notifications-link {{auth()->user()->hasNewNotifications() ? 'active' : null}}" data-toggle="fixed-panel" data-target="#notifications-panel">
        <i class="fas fa-fw fa-bell notification-bell"></i>
        <div class="notifications-count bg-white rounded-circle position-absolute font-weight-bold shadow-sm" style="bottom: -2px; right: 0;">
          <div class="d-flex flex-center w-100 h-100">{{auth()->user()->unreadNotifications->count()}}</div>
        </div>
      </a>
    </li>
    <button class="border-0 navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarResponsive">
      <span class="navbar-toggler-icon"></span>
    </button>
  </div>

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
          <li>
            <a class="py-2" href="{{route('admin.stats.quizzes')}}">Quizzes</a>
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
            <a class="py-2" href="{{route('admin.playlists.index')}}">Playlists</a>
          </li>
          <li>
            <a class="py-2" href="{{route('admin.composers.index')}}">Composers</a>
          </li>
          <li>
            <a class="py-2" href="{{route('admin.pianists.index')}}">Pianists</a>
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
          <li class="d-none d-sm-block">
            <a class="py-2" href="{{route('admin.posts.gifts.index')}}">Images</a>
          </li>
        </ul>
      </li>

      <li class="nav-item">
        <a class="nav-link nav-link-collapse collapsed" data-toggle="collapse" href="#quiz">
          <div>
            <i class="fas fa-book-open fa-fw"></i>
            <span class="nav-link-text">Quizzes</span>
          </div>
          <div>
            <i class="fas fa-caret-down"></i>
          </div>
        </a>
        <ul class="sidenav-second-level collapse" id="quiz">
          <li>
            <a class="py-2" href="{{route('admin.quizzes.index')}}">Quizzes</a>
          </li>
          <li>
            <a class="py-2" href="{{route('admin.quizzes.topics.index')}}">Topics</a>
          </li>
          <li class="d-none d-sm-block">
            <a class="py-2" href="{{route('admin.quizzes.media.audio')}}">Audio</a>
          </li>
          <li class="d-none d-sm-block">
            <a class="py-2" href="{{route('admin.quizzes.media.images')}}">Images</a>
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
        <a class="nav-link" href="{{route('admin.infographs.index')}}">
          <i class="fas fa-pencil-ruler fa-fw"></i>
          <span class="nav-link-text">Infographs</span>
        </a>
      </li>
{{--       <li class="nav-item d-none d-sm-block">
        <a class="nav-link" href="{{route('admin.editors.index')}}">
          <i class="fas fa-pencil-alt fa-fw"></i>
          <span class="nav-link-text">Editors</span>
        </a>
      </li> --}}
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
      <li class="nav-item hide-on-collapse">
        <a class="nav-link position-relative notifications-link {{auth()->user()->hasNewNotifications() ? 'active' : null}}" data-toggle="fixed-panel" data-target="#notifications-panel">
          <i class="fas fa-fw fa-bell notification-bell"></i><span class="inline-on-collapse ml-1">Notifications</span>
          <div class="notifications-count bg-white rounded-circle position-absolute font-weight-bold shadow-sm" style="bottom: 4px; right: 2px;">
            <div class="d-flex flex-center w-100 h-100">{{auth()->user()->unreadNotifications->count()}}</div>
          </div>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-toggle="modal" data-target="#logoutModal">
          <i class="fas fa-fw fa-sign-out-alt" style="font-size: 1.16em"></i><span class="ml-1 inline-on-collapse">Logout</span>
        </a>
      </li>
    </ul>
  </div>
</nav>

@include('components.panel.notifications')