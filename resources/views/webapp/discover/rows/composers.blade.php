<div class="mb-4">
	<div class="d-flex d-apart mb-3">
		<h5 class="m-0">Composers</h5>
		<button data-toggle="modal" data-target="#composers-modal" class="btn-raw link-primary">View all</button>
	</div>

	@include('webapp.components.grids.circles', [
		'collection' => $row['content'],
		'name' => 'name',
		'image' => 'cover_image',
		'count' => 'pieces_count'])
</div>