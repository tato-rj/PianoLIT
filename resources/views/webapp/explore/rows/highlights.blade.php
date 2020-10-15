@component('webapp.explore.rows.row', ['data' => $row])
	@include('webapp.components.grids.squares', ['collection' => $row['collection']])
@endcomponent