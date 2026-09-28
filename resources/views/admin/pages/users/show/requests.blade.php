<div class="row">
  <div class="col-12 mb-4">
	@table([
		'id' => 'requests-table',
  		'title' => 'Tutorial Requests (' . $user->tutorialRequests()->count() . ')',
		'sortable' => true,
		'headers' => ['Date ' . \App\Support\Icon::render('arrow-down-up', ['mr' => 0]) . '', 'Piece ' . \App\Support\Icon::render('arrow-down-up', ['mr' => 0]) . '', 'Composer ' . \App\Support\Icon::render('arrow-down-up', ['mr' => 0]) . '</th>', 'Level ' . \App\Support\Icon::render('arrow-down-up', ['mr' => 0]) . ''],
		'more' => route('admin.users.load-requests', $user->id),
		'rows' => view('admin.pages.users.show.requests.rows', [
			'user' => $user, 
			'requests' => $user->tutorialRequests->take(5)
		])
	])
  </div>
</div>
