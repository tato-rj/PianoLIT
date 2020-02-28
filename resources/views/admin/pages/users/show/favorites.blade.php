@include('admin.pages.users.show.title', ['title' => 'Favorites (' . $user->favorites_count . ')', 'icon' => 'heart'])

<div class="row">
  <div class="col-12 mb-4">
    @include('admin.pages.users.show.favorites.table')
  </div>
</div>
