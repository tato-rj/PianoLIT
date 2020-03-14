  <div class="collapse navbar-collapse" id="navbarResponsive">
    <ul class="navbar-nav navbar-sidenav">

      @editor
        @include('admin.layouts.nav.item', ['route' => null, 'name' => 'My profile', 'icon' => 'pencil-alt'])
      @endeditor
  
      @manager
        @include('admin.layouts.nav.item', ['route' => null, 'name' => 'Api', 'icon' => 'code',
        'dropdown' => [
          ['route' => route('admin.api.discover'), 'name' => 'Discover'],
          ['route' => route('admin.api.search'), 'name' => 'Search'],
          ['route' => route('admin.api.tour'), 'name' => 'Tour'],
        ]])

        @include('admin.layouts.nav.item', ['route' => null, 'name' => 'Statistics', 'icon' => 'chart-line',
        'dropdown' => [
          ['name' => 'Users', 'route' => route('admin.stats.users')],
          ['name' => 'Pieces', 'route' => route('admin.stats.pieces')],
          ['name' => 'Composers', 'route' => route('admin.stats.composers')],
          ['name' => 'Blog', 'route' => route('admin.stats.blog')],
          ['name' => 'Quizzes', 'route' => route('admin.stats.quizzes')],
        ]])
        
        <div class="dropdown-divider hide-on-sm"></div>
        
        @include('admin.layouts.nav.item', ['route' => null, 'name' => 'Users', 'icon' => 'users',
        'dropdown' => [
          ['name' => 'Profiles', 'route' => route('admin.users.index')],
          ['name' => 'Activity Logs', 'route' => route('admin.users.logs')],
          ['name' => 'Tutorial Requests', 'route' => route('admin.tutorial-requests.index')]
        ]])

        @include('admin.layouts.nav.item', ['route' => null, 'name' => 'Subscriptions', 'icon' => 'envelope',
        'dropdown' => [
          ['name' => 'Subscribers', 'route' => route('admin.subscriptions.index')],
          ['name' => 'Email lists', 'route' => route('admin.subscriptions.lists.index')],
          ['name' => 'Reports', 'route' => route('admin.subscriptions.reports.index')]
        ]])

        <div class="dropdown-divider hide-on-sm"></div>

        @include('admin.layouts.nav.item', ['route' => null, 'name' => 'Repertoire', 'icon' => 'music',
        'dropdown' => [
          ['route' => route('admin.pieces.index'), 'name' => 'Pieces'],
          ['route' => route('admin.playlists.index'), 'name' => 'Playlists'],
          ['route' => route('admin.composers.index'), 'name' => 'Composers'],
          ['route' => route('admin.pianists.index'), 'name' => 'Pianists'],
          ['route' => route('admin.tags.index'), 'name' => 'Tags'],
        ]])

        @include('admin.layouts.nav.item', ['route' => null, 'name' => 'Blog', 'icon' => 'newspaper',
        'dropdown' => [
          ['route' => route('admin.posts.index'), 'name' => 'Posts'],
          ['route' => route('admin.topics.index'), 'name' => 'Topics'],
          ['route' => route('admin.posts.audio.index'), 'name' => 'Audio'],
          ['route' => route('admin.posts.gifts.index'), 'name' => 'Images'],
        ]])

        @include('admin.layouts.nav.item', ['route' => null, 'name' => 'Quizzes', 'icon' => 'book-open',
        'dropdown' => [
          ['route' => route('admin.quizzes.index'), 'name' => 'Quizzes'],
          ['route' => route('admin.quizzes.topics.index'), 'name' => 'Topics'],
          ['route' => route('admin.quizzes.media.audio'), 'name' => 'Audio'],
          ['route' => route('admin.quizzes.media.images'), 'name' => 'Images'],
        ]])

        @include('admin.layouts.nav.item', ['route' => null, 'name' => 'Infographics', 'icon' => 'pencil-ruler',
        'dropdown' => [
          ['route' => route('admin.infographs.index'), 'name' => 'Designs'],
          ['route' => route('admin.infographs.topics.index'), 'name' => 'Topics'],
        ]])

        @include('admin.layouts.nav.item', ['route' => null, 'name' => 'Crash Courses', 'icon' => 'graduation-cap',
        'dropdown' => [
          ['name' => 'Courses', 'route' => route('admin.crashcourses.index')],
          ['name' => 'Topics', 'route' => route('admin.crashcourses.topics.index')],
          ['name' => 'Subscriptions', 'route' => route('admin.crashcourses.subscriptions.index')]
        ]])
        
        @include('admin.layouts.nav.item', ['route' => route('admin.timelines.index'), 'name' => 'Timeline', 'icon' => 'list-ul'])
      @endmanager

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