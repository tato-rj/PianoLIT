@include('admin.pages.users.show.title', ['title' => 'Tutorial Requests (' . $user->tutorialRequests()->count() . ')', 'icon' => 'chalkboard-teacher'])

<div class="row">
  <div class="col-12 mb-4">
	@table([
		'id' => 'requests-table',
		'sortable' => true,
		'headers' => ['Date <i class="fas fa-sort"></i>', 'Piece <i class="fas fa-sort"></i>', 'Composer <i class="fas fa-sort"></i></th>', 'Level <i class="fas fa-sort"></i>'],
		'more' => route('admin.users.load-requests', $user->id),
		'rows' => view('admin.pages.users.show.requests.rows', [
			'user' => $user, 
			'requests' => $user->tutorialRequests->take(5)
		])
	])
  </div>
</div>
