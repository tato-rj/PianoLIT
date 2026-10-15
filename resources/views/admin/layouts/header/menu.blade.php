<div class="collapse admin-sidebar-collapse" id="navbarResponsive">
  <div class="admin-sidebar-body">
    <ul class="admin-menu">

        @include('admin.layouts.header.divider', ['label' => 'TOOLS'])

        @include('admin.layouts.header.item', ['route' => route('admin.api.discover'), 'name' => 'Discover Tab', 'icon' => 'code'])
        
        @include('admin.layouts.header.item', ['route' => null, 'name' => 'Settings', 'icon' => 'gear',
        'dropdown' => [
          ['route' => route('admin.design-system.index'), 'name' => 'Design System'],
        ]])

        @include('admin.layouts.header.item', ['route' => null, 'name' => 'Analytics', 'icon' => 'chart-line',
        'dropdown' => [
          ['name' => 'Users', 'route' => route('admin.analytics.users')],
          ['name' => 'Memberships', 'route' => route('admin.analytics.memberships')],
          ['name' => 'Pieces', 'route' => route('admin.analytics.pieces')],
          ['name' => 'Favorites', 'route' => route('admin.analytics.favorites')],
          ['name' => 'Composers', 'route' => route('admin.analytics.composers')],
        ]])

        @include('admin.layouts.header.divider', ['label' => 'ACQUISITION'])

        @include('admin.layouts.header.item', ['route' => null, 'name' => 'Users', 'icon' => 'users',
        'dropdown' => [
          ['name' => 'Profiles', 'route' => route('admin.users.index')],
          ['name' => 'Locations', 'route' => route('admin.users.locations.index')],
          ['name' => 'Activity Logs', 'route' => route('admin.users.logs')],
          ['name' => 'Tutorial Requests', 'route' => route('admin.tutorial-requests.index')],
          ['name' => 'Feedback', 'route' => route('admin.feedback.index')],
          ['name' => 'Memberships Logs', 'route' => route('admin.memberships.logs'), 'target' => '_blank']
        ]])

        @include('admin.layouts.header.item', ['route' => null, 'name' => 'Subscriptions', 'icon' => 'mail',
        'dropdown' => [
          ['name' => 'Subscribers', 'route' => route('admin.subscriptions.index')],
          ['name' => 'Email Lists', 'route' => route('admin.subscriptions.lists.index')],
          ['name' => 'Reports', 'route' => route('admin.subscriptions.reports.index')]
        ]])

        @include('admin.layouts.header.divider', ['label' => 'PAID CONTENT'])

        @include('admin.layouts.header.item', ['route' => null, 'name' => 'Repertoire', 'icon' => 'music',
        'dropdown' => [
          ['route' => route('admin.pieces.index'), 'name' => 'Pieces'],
          ['route' => route('admin.timeline-events.index'), 'name' => 'Timeline events'],
          // ['route' => route('admin.stage.index'), 'name' => 'Stage'],
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

        @include('admin.layouts.header.item', ['route' => route('admin.reviews.index'), 'name' => 'Reviews', 'icon' => 'star-half'])

        @include('admin.layouts.header.divider', ['label' => 'FREE CONTENT'])

        @include('admin.layouts.header.item', ['route' => null, 'name' => 'Blog', 'icon' => 'newspaper',
        'dropdown' => [
          ['route' => route('admin.posts.index'), 'name' => 'Posts'],
          ['route' => route('admin.topics.index'), 'name' => 'Topics'],
          ['route' => route('admin.posts.audio.index'), 'name' => 'Audio'],
          ['route' => route('admin.posts.gifts.index'), 'name' => 'Gifts'],
        ]])

        @include('admin.layouts.header.item', ['route' => null, 'name' => 'Quizzes', 'icon' => 'circle-help',
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

        @include('admin.layouts.header.item', ['route' => route('admin.clips.index'), 'name' => 'Video clips', 'icon' => 'circle-play'])

    </ul>

{{--     <div class="admin-sidebar-footer">
      <span class="admin-theme-label">Light mode <small>Coming soon</small></span>
      <div class="admin-theme-switch" role="group" aria-label="Appearance (coming soon)">
        <button type="button" disabled aria-label="Light mode (current)">@icon('sun', ['mr' => 0])</button>
        <button type="button" disabled aria-label="Dark mode (coming soon)">@icon('moon', ['mr' => 0])</button>
      </div>
    </div> --}}
  </div>
</div>
