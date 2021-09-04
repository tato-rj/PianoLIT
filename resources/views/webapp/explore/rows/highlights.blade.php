@component('webapp.explore.rows.row', [
	'data' => $row,
	'link' => ['url' => route('webapp.highlights'), 'label' => 'View all']
	])
	@include('webapp.components.grids.squares', ['collection' => $row['collection']])
@endcomponent