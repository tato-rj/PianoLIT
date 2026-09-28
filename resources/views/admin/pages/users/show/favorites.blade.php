<div class="row">
  <div class="col-12 mb-4">
	@table([
		'id' => 'favorites-table',
  		'title' => 'Favorites (' . $user->favorites_count . ')',
		'sortable' => true,
		'headers' => ['Piece ' . \App\Support\Icon::render('arrow-down-up', ['mr' => 0]) . '', 'Composer ' . \App\Support\Icon::render('arrow-down-up', ['mr' => 0]) . '</th>', 'Level ' . \App\Support\Icon::render('arrow-down-up', ['mr' => 0]) . ''],
		'more' => route('admin.users.load-favorites', $user->id),
		'rows' => view('admin.pages.users.show.favorites.rows', [
			'user' => $user, 
			'pieces' => $user->favorites->take(5)
		])
	])
  </div>
</div>
