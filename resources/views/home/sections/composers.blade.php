		<div class="col-12 mb-5">
			@include('webapp.components.grids.circles', [
				'collection' => $composers,
				'route' => 'explore.search',
				'name' => 'name',
				'image' => 'cover_image',
				'count' => 'pieces_count'])
		</div>