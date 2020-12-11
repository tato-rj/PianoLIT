  <div class="collapse navbar-collapse" id="navbarResponsive">
    <ul class="navbar-nav navbar-sidenav">

      @editor
        @include('admin.layouts.header.item', ['route' => null, 'name' => 'My profile', 'icon' => 'pencil-alt'])
      @endeditor
  
      @manager

        @include('admin.layouts.header.divider', ['label' => 'TOOLS'])

        @include('admin.layouts.header.item', ['route' => null, 'name' => 'Api', 'icon' => 'code',
        'dropdown' => [
          ['route' => route('admin.api.discover'), 'name' => 'Discover tab'],
          ['route' => route('admin.api.endpoints'), 'name' => 'Endpoints'],
        ]])

        @include('admin.layouts.header.item', ['route' => null, 'name' => 'Statistics', 'icon' => 'chart-line',
        'dropdown' => [
          ['name' => 'Users', 'route' => route('admin.stats.users')],
          ['name' => 'Memberships', 'route' => route('admin.stats.memberships')],
          ['name' => 'Subscriptions', 'route' => route('admin.stats.subscriptions')],
          ['name' => 'Pieces', 'route' => route('admin.stats.pieces')],
          ['name' => 'Composers', 'route' => route('admin.stats.composers')],
          ['name' => 'Blog', 'route' => route('admin.stats.blog')],
          ['name' => 'Quizzes', 'route' => route('admin.stats.quizzes')],
        ]])
        
        @include('admin.layouts.header.divider', ['label' => 'ACQUISITION'])
        
        @include('admin.layouts.header.item', ['route' => null, 'name' => 'Users', 'icon' => 'users',
        'dropdown' => [
          ['name' => 'Profiles', 'route' => route('admin.users.index')],
          ['name' => 'Activity Logs', 'route' => route('admin.users.logs')],
          ['name' => 'Tutorial Requests', 'route' => route('admin.tutorial-requests.index')],
          ['name' => 'Memberships logs', 'route' => route('admin.memberships.logs'), 'target' => '_blank']
        ]])

        @include('admin.layouts.header.item', ['route' => null, 'name' => 'Subscriptions', 'icon' => 'envelope',
        'dropdown' => [
          ['name' => 'Subscribers', 'route' => route('admin.subscriptions.index')],
          ['name' => 'Email lists', 'route' => route('admin.subscriptions.lists.index')],
          ['name' => 'Reports', 'route' => route('admin.subscriptions.reports.index')]
        ]])

        @include('admin.layouts.header.divider', ['label' => 'PAID CONTENT'])

        @include('admin.layouts.header.item', ['route' => null, 'name' => 'Repertoire', 'icon' => 'music',
        'dropdown' => [
          ['route' => route('admin.pieces.index'), 'name' => 'Pieces'],
          ['route' => route('admin.playlists.index'), 'name' => 'Playlists'],
          ['route' => route('admin.composers.index'), 'name' => 'Composers'],
          ['route' => route('admin.pianists.index'), 'name' => 'Pianists'],
          ['route' => route('admin.tags.index'), 'name' => 'Tags'],
        ]])

        @include('admin.layouts.header.item', ['route' => null, 'name' => 'Shop', 'icon' => 'shopping-cart',
        'dropdown' => [
          ['name' => 'eBooks', 'route' => route('admin.ebooks.index')],
          ['name' => 'eBook Topics', 'route' => route('admin.ebooks.topics.index')],
          [],
          ['name' => 'eScores', 'route' => route('admin.escores.index')],
          ['name' => 'eScore Topics', 'route' => route('admin.escores.topics.index')]
        ]])

        @include('admin.layouts.header.divider', ['label' => 'FREE CONTENT'])

        @include('admin.layouts.header.item', ['route' => null, 'name' => 'Blog', 'icon' => 'newspaper',
        'dropdown' => [
          ['route' => route('admin.posts.index'), 'name' => 'Posts'],
          ['route' => route('admin.topics.index'), 'name' => 'Topics'],
          ['route' => route('admin.posts.audio.index'), 'name' => 'Audio'],
          ['route' => route('admin.posts.gifts.index'), 'name' => 'Images'],
        ]])

        @include('admin.layouts.header.item', ['route' => null, 'name' => 'Quizzes', 'icon' => 'question-circle',
        'dropdown' => [
          ['route' => route('admin.quizzes.index'), 'name' => 'Quizzes'],
          ['route' => route('admin.quizzes.topics.index'), 'name' => 'Topics'],
          ['route' => route('admin.quizzes.media.audio'), 'name' => 'Audio'],
          ['route' => route('admin.quizzes.media.images'), 'name' => 'Images'],
        ]])

        @include('admin.layouts.header.item', ['route' => null, 'name' => 'Infographics', 'icon' => 'pencil-ruler',
        'dropdown' => [
          ['route' => route('admin.infographs.index'), 'name' => 'Designs'],
          ['route' => route('admin.infographs.topics.index'), 'name' => 'Topics'],
        ]])

        @include('admin.layouts.header.item', ['route' => null, 'name' => 'Crash Courses', 'icon' => 'graduation-cap',
        'dropdown' => [
          ['name' => 'Courses', 'route' => route('admin.crashcourses.index')],
          ['name' => 'Topics', 'route' => route('admin.crashcourses.topics.index')],
          ['name' => 'Subscriptions', 'route' => route('admin.crashcourses.subscriptions.index')]
        ]])

        @include('admin.layouts.header.divider', ['label' => 'EXTRA'])
        
        @include('admin.layouts.header.item', ['route' => null, 'name' => 'Media', 'icon' => 'play-circle',
        'dropdown' => [
          ['route' => route('admin.clips.index'), 'name' => 'Clips'],
        ]])

        @include('admin.layouts.header.item', ['route' => route('admin.timelines.index'), 'name' => 'Timeline', 'icon' => 'list-ul'])
      @endmanager

    </ul>

    <ul class="navbar-nav ml-auto">
      <li class="nav-item hide-on-collapse">
        <a class="nav-link position-relative notifications-link {{auth()->user()->hasNewNotifications() ? 'active' : null}}" data-toggle="fixed-panel" data-target="#notifications-panel">
          @fa(['icon' => 'bell', 'classes' => 'notification-bell'])<span class="inline-on-collapse ml-1">Notifications</span>
          <div class="notifications-count text-dark bg-white rounded-circle position-absolute font-weight-bold shadow-sm" style="bottom: 4px; right: 2px;">
            <div class="d-flex flex-center w-100 h-100">{{auth()->user()->unreadNotifications->count()}}</div>
          </div>
        </a>
      </li>
      <li class="nav-item">
        <form id="logout-form" action="{{ route('admin.logout') }}" method="POST" style="display: none;">
          @csrf
        </form>
        <a class="nav-link" href="" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
          @fa(['icon' => 'sign-out-alt', 'size' => 'lg'])<span class="ml-1 inline-on-collapse">Logout</span>
        </a>
      </li>
    </ul>
  </div>