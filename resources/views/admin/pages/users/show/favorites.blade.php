@include('admin.pages.users.show.title', ['title' => 'Favorites (' . $user->favorites_count . ')', 'icon' => 'heart'])

<div class="row">
  <div class="col-12 mb-4">
	@table([
		'id' => 'favorites-table',
		'headers' => ['Piece <i class="fas fa-sort"></i>', 'Composer <i class="fas fa-sort"></i></th>', 'Level <i class="fas fa-sort"></i>'],
		'rows' => view('admin.pages.users.show.favorites.rows', ['user' => $user, 'pieces' => $user->favorites, 'limit' => 5, 'more' => true])
	])
  </div>
</div>
