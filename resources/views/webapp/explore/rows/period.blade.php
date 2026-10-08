@component('webapp.explore.rows.row', ['data' => $row])
	@include('webapp.components.grids.circles', [
		'collection' => $row['collection'],
		'name' => 'name',
		'image' => 'web_cover_image',
		'count' => 'pieces_count'])
@endcomponent
