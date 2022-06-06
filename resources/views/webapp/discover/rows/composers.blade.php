<div class="mb-4">
	<div class="d-flex d-apart mb-3">
		<h5 class="m-0">Composers</h5>
		<a href="{{route('webapp.composers.index')}}" class="btn-raw link-primary">View all</a>
	</div>

	@include('webapp.components.grids.circles', [
		'collection' => $row['content'],
		'name' => 'name',
		'image' => 'cover_image',
		'count' => 'pieces_count'])
</div>