<div class="row">
  <div class="col-12 mb-4">
	@table([
		'id' => 'favorites-table',
  		'title' => 'Favorites (' . $user->favorites_count . ')',
		'sortable' => true,
		'headers' => ['Piece <i class="fas fa-sort"></i>', 'Composer <i class="fas fa-sort"></i></th>', 'Level <i class="fas fa-sort"></i>'],
		'more' => route('admin.users.load-favorites', $user->id),
		'rows' => view('admin.pages.users.show.favorites.rows', [
			'user' => $user, 
			'pieces' => $user->favorites->take(5)
		])
	])
  </div>
</div>
